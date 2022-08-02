<?php

namespace Plastonick\FantasyDatabase\Hydration;

use League\Csv\Reader;
use PDO;

use Psr\Log\LoggerInterface;

use function scandir;

class FixturesHydration
{
    use ExtractionTrait, DataMapTrait;

    const HEADERS = [
        'event' => 'integer', # game week (unique to season-level)
        'fixture' => 'integer', # FPL's season-unique fixture ID
        'finished' => 'bool',
        'finished_provisional' => 'bool',
        'kickoff_time' => 'datetime',
        'team_a_score' => 'integer',
        'team_h_score' => 'integer',
        'team_h_difficulty' => 'integer',
        'team_a_difficulty' => 'integer',
    ];

    public function __construct(private readonly PDO $pdo, private readonly LoggerInterface $logger)
    {
    }

    /**
     * @param string $dataPath
     *
     * @return void
     * @throws \League\Csv\Exception
     */
    public function hydrate(string $dataPath): void
    {
        $seasonNameIdMap = $this->getSeasonNameIdMap();
        $yearTeamIds = $this->getTeamIdMaps($dataPath);
        foreach (scandir($dataPath) as $season) {
            if (in_array($season, ['.', '..'])) {
                continue;
            }

            $seasonId = $seasonNameIdMap[$season] ?? null;

            if (!$seasonId) {
                continue;
            }

            $this->logger->info('Generating fixtures for season', ['season' => $season]);

            $teamIdMap = $yearTeamIds[$season];
            $fixturesPath = "{$dataPath}/{$season}/fixtures.csv";

            if (is_file($fixturesPath)) {
                $this->hydrateFromFixturesList($fixturesPath, $teamIdMap, $seasonId);
            } else {
                // prefer using fixtures list where possible, since this includes away/home difficulty
                $this->hydrateFromMergedGws("{$dataPath}/{$season}", $teamIdMap, $seasonId);
            }
        }
    }

    private function hydrateFromFixturesList(string $fixturesPath, array $teamIdMap, int $seasonId): void
    {
        $reader = Reader::createFromPath($fixturesPath);
        $reader->setHeaderOffset(0);

        foreach ($reader as $row) {
            $values = [];
            foreach (self::HEADERS as $header => $type) {
                $extractData = $this->extractData($type, $row[$header] ?? null);
                $values[$header] = $extractData;
            }

            $values['fixture'] = (int) $row['id'];
            $values['season_id'] = $seasonId;
            $values['away_team_id'] = $teamIdMap[(int) $row['team_a']];
            $values['home_team_id'] = $teamIdMap[(int) $row['team_h']];

            $this->insertFixture($values);
        }
    }

    private function hydrateFromMergedGws(string $yearPath, array $teamIdMap, int $seasonId): void
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
                    'fixture' => $this->extractData('integer', $row['fixture']),
                    'finished' => true,
                    'finished_provisional' => true,
                    'kickoff_time' => $this->extractData('datetime', $row['kickoff_time']),
                    'team_a_score' => $this->extractData('integer', $row['team_a_score']),
                    'team_h_score' => $this->extractData('integer', $row['team_h_score']),
                    'team_h_difficulty' => null, // sadly, this data seems to be lost to time now!
                    'team_a_difficulty' => null, // ^
                    'season_id' => $seasonId,
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
     * @param array $values
     *
     * @return void
     */
    private function insertFixture(array $values): void
    {
        $databaseColumns = array_merge(array_keys(self::HEADERS), ['away_team_id', 'home_team_id', 'season_id']);
        $columnsString = implode(',', $databaseColumns);
        $insertString = implode(',', array_map(fn($var) => ':' . $var, $databaseColumns));
        $sql = "INSERT INTO fixtures ({$columnsString}) VALUES ({$insertString})";

        $statement = $this->pdo->prepare($sql);
        $statement->execute($values);
    }

    private function getSeasonNameIdMap(): array
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
}
