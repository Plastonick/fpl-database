<?php

namespace Plastonick\FantasyDatabase\Hydration;

use DateTime;
use League\Csv\Reader;
use PDO;
use function scandir;

class PlayerHydration
{
    use ExtractionTrait;

    const HEADERS = [
        'round' => 'integer', # game week id
        'finished' => 'bool',
        'finished_provisional' => 'bool',
        'id' => 'integer',
        'kickoff_time' => 'datetime',
        'minutes' => 'integer',
        'provisional_start_time' => 'bool',
        'started' => 'bool',
        'team_a_score' => 'integer',
        'team_h_score' => 'integer',
        'stats' => 'json',
        'team_h_difficulty' => 'integer',
        'team_a_difficulty' => 'integer',
        'pulse_id' => 'integer',
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

        $boolTypes = [];

        foreach (scandir($dataPath) as $year) {
            if (in_array($year, ['.', '..'])) {
                continue;
            }

            $seasonId = $seasonNameIdMap[$year] ?? null;

            if (!$seasonId) {
                continue;
            }

            $teamIdMap = $yearTeamIds[$year];
            $gameWeekRoundIdMap = $this->getGameWeekRoundIdMap($seasonId);

            $mergedGameWeeksPath = "{$dataPath}/{$year}/gws/merged_gw.csv";

            if (!is_file($mergedGameWeeksPath)) {
                throw new \Exception('Failed to find merged gameweeks file');
            }

            $reader = Reader::createFromPath($mergedGameWeeksPath);
            $reader->setHeaderOffset(0);

            $fixtures = [];

            foreach ($reader as $row) {
                if (isset($fixtures[$row['fixture']])) {
                    continue;
                }

                $fixtures[$row['fixture']] = true;

                $values = [];
                $rowInserts = [];
                foreach (self::HEADERS as $header => $type) {
                    $extractData = $this->extractData($type, $row[$header] ?? null);
                    if ($type === 'bool') {
                        if (!in_array($extractData, $boolTypes)) {
                            $boolTypes[] = $extractData;
                        }
                    }

                    $values[] = $extractData;
                    $rowInserts[] = '?';
                }

                $values[] = $teamIdMap[$row['a_team']];
                $rowInserts[] = '?';
                $values[] = $teamIdMap[$row['h_team']];
                $rowInserts[] = '?';


                $databaseColumns = implode(
                    ',',
                    array_merge(array_keys(self::HEADERS), ['away_team_id', 'home_team_id'])
                );
                $insertString = implode(',', $rowInserts);
                $sql = "INSERT INTO fixtures ({$databaseColumns}) VALUE ({$insertString})";

                $statement = $this->pdo->prepare($sql);
                $statement->execute($values);
            }
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

    private function getGameWeekRoundIdMap(int $seasonId)
    {
        $sql = <<<SQL
SELECT game_week_id, round FROM game_weeks WHERE season_id = ?
SQL;

        $statement = $this->pdo->prepare($sql);
        $statement->execute([$seasonId]);
        $results = $statement->fetchAll();

        $map = [];
        foreach ($results as [$gameWeekId, $round]) {
            $map[$round] = $gameWeekId;
        }

        return $map;
    }
}
