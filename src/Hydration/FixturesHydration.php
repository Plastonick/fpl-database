<?php

namespace Plastonick\FantasyDatabase\Hydration;

use League\Csv\Reader;
use PDO;

use function scandir;

class FixturesHydration
{
    use ExtractionTrait;

    const HEADERS = [
        'event' => 'integer', # game week id/round
        'finished' => 'bool',
        'finished_provisional' => 'bool',
        'kickoff_time' => 'datetime',
        'team_a_score' => 'integer',
        'team_h_score' => 'integer',
        'team_h_difficulty' => 'integer',
        'team_a_difficulty' => 'integer',
    ];

    public function __construct(private PDO $pdo)
    {
    }

    /**
     * @param string $dataPath
     *
     * @return void
     * @throws \League\Csv\Exception
     */
    public function hydrate(string $dataPath)
    {
        $seasonNameIdMap = $this->getSeasonNameIdMap();
        $yearTeamIds = $this->getTeamIdMaps($dataPath);
        foreach (scandir($dataPath) as $year) {
            if (in_array($year, ['.', '..'])) {
                continue;
            }

            $seasonId = $seasonNameIdMap[$year] ?? null;

            if (!$seasonId) {
                continue;
            }

            $teamIdMap = $yearTeamIds[$year];
            $gameWeekEventIdMap = $this->getGameWeekEventIdMap($seasonId);

            $fixturesPath = "{$dataPath}/{$year}/fixtures.csv";

            if (!is_file($fixturesPath)) {
                $this->hydrateFromMergedGws("{$dataPath}/{$year}", $teamIdMap, $gameWeekEventIdMap);
            } else {
                $this->hydrateFromFixturesList($fixturesPath, $gameWeekEventIdMap, $teamIdMap);
            }
        }
    }

    /**
     * @param string $fixturesPath
     * @param array $gameWeekEventIdMap
     * @param mixed $teamIdMap
     *
     * @return void
     * @throws \League\Csv\Exception
     */
    protected function hydrateFromFixturesList(string $fixturesPath, array $gameWeekEventIdMap, mixed $teamIdMap): void
    {
        $reader = Reader::createFromPath($fixturesPath);
        $reader->setHeaderOffset(0);

        foreach ($reader as $row) {
            $values = [];
            foreach (self::HEADERS as $header => $type) {
                $extractData = $this->extractData($type, $row[$header] ?? null);
                $values[$header] = $extractData;
            }

            $values['game_week_id'] = $gameWeekEventIdMap[(int) $row['event']];
            $values['away_team_id'] = $teamIdMap[(int) $row['team_a']];
            $values['home_team_id'] = $teamIdMap[(int) $row['team_h']];

            $this->insertFixture($values);
        }
    }

    /**
     * @param array $values
     *
     * @return void
     */
    protected function insertFixture(array $values): void
    {
        $databaseColumns = array_merge(array_keys(self::HEADERS), ['game_week_id', 'away_team_id', 'home_team_id']);
        $columnsString = implode(',', $databaseColumns);
        $insertString = implode(',', array_map(fn($var) => ':' . $var, $databaseColumns));
        $sql = "INSERT INTO fixtures ({$columnsString}) VALUES ({$insertString})";

        $statement = $this->pdo->prepare($sql);
        $statement->execute($values);
    }

    private function hydrateFromMergedGws(string $yearPath, array $teamIdMap, array $gameWeekEventIdMap)
    {
        $mergedGwsPath = "{$yearPath}/gws/merged_gw.csv";
        $reader = Reader::createFromPath($mergedGwsPath);
        $reader->setHeaderOffset(0);

        $fixtures = [];
        foreach ($reader as $row) {
            if (!isset($fixtures[$row['fixture']])) {
                $round = $this->extractData('integer', $row['round']);

                $fixtures[$row['fixture']] = [
                    'event' => $round,
                    'finished' => true,
                    'finished_provisional' => true,
                    'kickoff_time' => $this->extractData('datetime', $row['kickoff_time']),
                    'team_a_score' => $this->extractData('integer', $row['team_a_score']),
                    'team_h_score' => $this->extractData('integer', $row['team_h_score']),
                    'team_h_difficulty' => null,
                    'team_a_difficulty' => null,
                    'game_week_id' => $gameWeekEventIdMap[$round],
                    'away_team_id' => null,
                    'home_team_id' => null,
                ];
            }

            if ($this->extractData('bool', $row['was_home'])) {
                $fixtures[$row['fixture']]['away_team_id'] = $teamIdMap[$row['opponent_team']];
            } else {
                $fixtures[$row['fixture']]['home_team_id'] = $teamIdMap[$row['opponent_team']];
            }
        }

        foreach ($fixtures as $fixture) {
            $this->insertFixture($fixture);
        }
    }


    /**
     * @param string $dataPath
     *
     * @return array
     * @throws \League\Csv\Exception
     */
    protected function getTeamIdMaps(string $dataPath): array
    {
        $sql = <<<SQL
SELECT team_id, name FROM teams
SQL;

        $statement = $this->pdo->prepare($sql);
        $statement->execute();
        $results = $statement->fetchAll();

        $map = [];
        foreach ($results as [$teamId, $name]) {
            $map[$name] = $teamId;
        }

        $reader = Reader::createFromPath("{$dataPath}/master_team_list.csv");
        $reader->setHeaderOffset(0);

        $yearTeamIds = [];
        foreach ($reader as $row) {
            $yearTeamIds[$row['season']][$row['team']] = $map[$row['team_name']];
        }

        return $yearTeamIds;
    }

    private function getSeasonNameIdMap()
    {
        $sql = <<<SQL
SELECT season_id, name FROM seasons
SQL;

        $statement = $this->pdo->prepare($sql);
        $statement->execute();
        $results = $statement->fetchAll();

        $map = [];
        foreach ($results as [$seasonId, $name]) {
            $map[$name] = $seasonId;
        }

        return $map;
    }

    private function getGameWeekEventIdMap(int $seasonId)
    {
        $sql = <<<SQL
SELECT game_week_id, event FROM game_weeks WHERE season_id = ?
SQL;

        $statement = $this->pdo->prepare($sql);
        $statement->execute([$seasonId]);
        $results = $statement->fetchAll();

        $map = [];
        foreach ($results as [$gameWeekId, $event]) {
            $map[$event] = $gameWeekId;
        }

        return $map;
    }
}
