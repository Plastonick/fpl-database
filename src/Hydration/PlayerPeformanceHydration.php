<?php

namespace Plastonick\FantasyDatabase\Hydration;

use League\Csv\AbstractCsv;
use League\Csv\Reader;
use PDO;

class PlayerPeformanceHydration
{
    use ExtractionTrait;

    const HEADERS = [
        'assists' => 'integer',
        'attempted_passes' => 'integer',
        'big_chances_created' => 'integer',
        'big_chances_missed' => 'integer',
        'bonus' => 'integer',
        'bps' => 'integer',
        'clean_sheets' => 'integer',
        'clearances_blocks_interceptions' => 'integer',
        'completed_passes' => 'integer',
        'creativity' => 'decimal',
        'dribbles' => 'integer',
        'ea_index' => 'integer',
        'element' => 'integer',
        'errors_leading_to_goal' => 'integer',
        'errors_leading_to_goal_attempt' => 'integer',
        'fixture' => 'integer',
        'fouls' => 'integer',
        'goals_conceded' => 'integer',
        'goals_scored' => 'integer',
        'ict_index' => 'integer',
        'id' => 'integer',
        'influence' => 'decimal',
        'key_passes' => 'integer',
        'kickoff_time' => 'datetime',
        'kickoff_time_formatted' => 'string',
        'loaned_in' => 'integer',
        'loaned_out' => 'integer',
        'minutes' => 'integer',
        'offside' => 'integer',
        'open_play_crosses' => 'integer',
        'opponent_team' => 'integer',
        'own_goals' => 'integer',
        'penalties_conceded' => 'integer',
        'penalties_missed' => 'integer',
        'penalties_saved' => 'integer',
        'recoveries' => 'integer',
        'red_cards' => 'integer',
        'round' => 'integer',
        'saves' => 'integer',
        'selected' => 'integer',
        'tackled' => 'integer',
        'tackles' => 'integer',
        'target_missed' => 'integer',
        'team_a_score' => 'integer',
        'team_h_score' => 'integer',
        'threat' => 'decimal',
        'total_points' => 'integer',
        'transfers_balance' => 'integer',
        'transfers_in' => 'integer',
        'transfers_out' => 'integer',
        'value' => 'integer',
        'was_home' => 'bool',
        'winning_goals' => 'integer',
        'yellow_cards' => 'integer',
    ];

    const HISTORY_HEADERS = [
        'assists' => 'integer',
        'bonus' => 'integer',
        'bps' => 'integer',
        'clean_sheets' => 'integer',
        'creativity' => 'decimal',
        'element_code' => 'integer',
        'end_cost' => 'integer',
        'goals_conceded' => 'integer',
        'goals_scored' => 'integer',
        'ict_index' => 'decimal',
        'influence' => 'decimal',
        'minutes' => 'integer',
        'own_goals' => 'integer',
        'penalties_missed' => 'integer',
        'penalties_saved' => 'integer',
        'red_cards' => 'integer',
        'saves' => 'integer',
        'season_name' => 'string',
        'start_cost' => 'integer',
        'threat' => 'decimal',
        'total_points' => 'integer',
        'yellow_cards' => 'integer',
    ];

    /** @var int[] */
    private array $playerIdMap = [];

    /** @var int[][]|null */
    private ?array $fixtureIdMap;

    public function __construct(private PDO $pdo)
    {
    }

    public function hydrate(string $dataPath)
    {
        $seasonNameIdMap = $this->getSeasonNameIdMap();
        foreach (scandir($dataPath) as $year) {
            if (in_array($year, ['.', '..'])) {
                continue;
            }

            $yearPlayersPath = "{$dataPath}/{$year}/players/";
            if (!is_dir($yearPlayersPath)) {
                continue;
            }

            $seasonId = $seasonNameIdMap[$year];

            foreach (scandir($yearPlayersPath) as $player) {
                if (in_array($player, ['.', '..'])) {
                    continue;
                }

                $yearPlayerGw = "{$yearPlayersPath}/{$player}/gw.csv";

                preg_match('/^([^\d\s_]+)(?:(?:[_\s])*.*[_\s])([^\d\s_]+)(_\d+)?$/', $player, $matches);
                $firstName = $matches[1];
                $secondName = $matches[2];
                $elementId = $matches[3] ?? null;
                $playerId = $this->getPlayerGlobalId($firstName, $secondName);

                if (is_file($yearPlayerGw)) {
                    $reader = Reader::createFromPath($yearPlayerGw);
                    $reader->setHeaderOffset(0);

                    $this->insertPlayerPerformances($reader, $playerId, $seasonId);
                } else {
                    echo "No GW detected for player {$player}!\n";
                }

                $yearPlayerHistory = "{$yearPlayersPath}/{$player}/history.csv";
                if (is_file($yearPlayerHistory)) {
                    $reader = Reader::createFromPath($yearPlayerHistory);
                    $reader->setHeaderOffset(0);

                    $this->insertPlayerHistories($reader, $playerId, $seasonNameIdMap);
                }
            }
        }
    }

    /**
     * @param AbstractCsv $reader
     * @param int $playerId
     * @param int $seasonId
     *
     * @return void
     */
    protected function insertPlayerPerformances(AbstractCsv $reader, int $playerId, int $seasonId): void
    {
        $columnsString = implode(',', array_merge(array_keys(self::HEADERS), ['player_id', 'fixture_id']));
        $sql = 'INSERT INTO player_performances (' . $columnsString . ') VALUES';

        $inserts = [];
        $values = [];
        foreach ($reader as $row) {
            $fixtureId = $this->getFixtureGlobalId($seasonId, $row['fixture']);
            $rowInserts = [];
            foreach (self::HEADERS as $header => $type) {
                $extractData = $this->extractData($type, $row[$header] ?? null);
                $values[] = $extractData;
                $rowInserts[] = '?';
            }
            $values[] = $playerId;
            $values[] = $fixtureId;
            $rowInserts[] = '?';
            $rowInserts[] = '?';
            $inserts[] = '(' . implode(',', $rowInserts) . ')';
        }

        $sql .= implode(',', $inserts);

        $statement = $this->pdo->prepare($sql);
        $statement->execute($values);
    }

    /**
     * @param AbstractCsv $reader
     * @param int $playerId
     * @param array $seasonNameIdMap
     *
     * @return void
     */
    protected function insertPlayerHistories(AbstractCsv $reader, int $playerId, array $seasonNameIdMap): void
    {
        $columnsString = implode(',', array_merge(array_keys(self::HISTORY_HEADERS), ['player_id', 'season_id']));
        $sql = 'INSERT INTO player_histories (' . $columnsString . ') VALUES';

        $inserts = [];
        $values = [];
        foreach ($reader as $row) {
            $rowInserts = [];
            foreach (self::HISTORY_HEADERS as $header => $type) {
                $extractData = $this->extractData($type, $row[$header] ?? null);
                $values[] = $extractData;
                $rowInserts[] = '?';
            }
            $values[] = $playerId;
            $seasonId = $seasonNameIdMap[str_replace('/', '-', $row['season_name'])];

            $values[] = $seasonId;
            $rowInserts[] = '?';
            $rowInserts[] = '?';
            $inserts[] = '(' . implode(',', $rowInserts) . ')';
        }

        $sql .= implode(',', $inserts);
        $sql .= ' ON CONFLICT (season_id, player_id) DO UPDATE 
  SET season_id = excluded.season_id';

        $statement = $this->pdo->prepare($sql);
        $statement->execute($values);
    }

    private function getPlayerGlobalId($firstName, $secondName): int
    {
        // TODO this will duplicate players with the same first/second name

        $key = $firstName . '~' . $secondName;

        if (!isset($this->playerIdMap[$key])) {
            $sql = 'INSERT INTO players (first_name, second_name) VALUES (?, ?)';
            $statement = $this->pdo->prepare($sql);
            $statement->execute([$firstName, $secondName]);

            $statement = $this->pdo->prepare('SELECT player_id FROM players WHERE first_name = ? AND second_name = ?');
            $statement->execute([$firstName, $secondName]);
            $this->playerIdMap[$key] = (int) $statement->fetchColumn();
        }

        return $this->playerIdMap[$key];
    }

    private function getFixtureGlobalId(int $seasonId, int $localFixture): int
    {
        if (!isset($this->fixtureIdMap)) {
            $this->fixtureIdMap = $this->buildFixtureMap();
        }

        return $this->fixtureIdMap[$seasonId][$localFixture];
    }

    private function buildFixtureMap(): array
    {
        $sql = <<<SQL
SELECT season_id, fixture, fixture_id 
FROM fixtures
SQL;

        $statement = $this->pdo->prepare($sql);
        $statement->execute();

        $map = [];
        foreach ($statement->fetchAll() as [$seasonId, $fixture, $fixtureId]) {
            $map[$seasonId][$fixture] = $fixtureId;
        }

        return $map;
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
