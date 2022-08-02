<?php

namespace Plastonick\FantasyDatabase\Hydration;

use Exception;
use League\Csv\AbstractCsv;
use League\Csv\Reader;
use PDO;
use Plastonick\FantasyDatabase\PlayerPersistence;
use Psr\Log\LoggerInterface;
use Throwable;

class PlayerPeformanceHydration
{
    use ExtractionTrait, DataMapTrait;

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

    /** @var int[][]|null */
    private ?array $fixtureIdMap;

    public function __construct(private readonly PDO $pdo, private readonly LoggerInterface $logger)
    {
    }

    /**
     * @throws Throwable
     */
    public function hydrate(string $dataPath)
    {
        $seasonNameIdMap = $this->getSeasonNameIdMap();
        $yearTeamIds = $this->getTeamIdMaps($dataPath);
        foreach (scandir($dataPath) as $season) {
            if (in_array($season, ['.', '..'])) {
                continue;
            }

            $yearPlayersPath = "{$dataPath}/{$season}/players/";
            if (!is_dir($yearPlayersPath)) {
                continue;
            }

            $playerData = "{$dataPath}/{$season}/players_raw.csv";
            if (!is_file($playerData)) {
                throw new Exception('Failed to retrieve player-id-list CSV');
            }

            $this->logger->info('Generating player data for season', ['season' => $season]);

            $playerIdListReader = Reader::createFromPath($playerData);
            $playerIdListReader->setHeaderOffset(0);

            /** @var PlayerData[] $playerElementMap */
            $playerElementMap = [];

            $seasonId = $seasonNameIdMap[$season];
            $teamIds = $yearTeamIds[$season];

            $this->logger->info('Some teams', ['teamIds' => $teamIds, 'season' => $season]);
            if (!isset($teamIds)) {
                $this->logger->error('No teams', ['yearTeamIds' => $yearTeamIds, 'season' => $season]);
                die;
            }

            foreach ($playerIdListReader as $row) {
                $playerElementMap[$row['id']] = new PlayerData(
                    $row['first_name'],
                    $row['second_name'],
                    $row['web_name'],
                    $row['code'],
                    $row['element_type'],
                    $teamIds[$row['team']]
                );
            }

            $playerPersistence = new PlayerPersistence(
                $this->pdo,
                $seasonId,
                $this->logger
            );

            foreach (scandir($yearPlayersPath) as $player) {
                if (str_starts_with($player, '.')) {
                    continue;
                }

                $yearPlayerGw = "{$yearPlayersPath}/{$player}/gw.csv";

                if (is_file($yearPlayerGw)) {
                    $gwReader = Reader::createFromPath($yearPlayerGw);
                    $gwReader->setHeaderOffset(0);
                } else {
                    $this->logger->notice('No GW detected for player!', ['player' => $player]);
                }

                $yearPlayerHistory = "{$yearPlayersPath}/{$player}/history.csv";
                $history = $this->readHistory($yearPlayerHistory);

                $element = (int) $gwReader->fetchOne(0)['element'];
                $playerId = $playerPersistence->matchPlayer($element, $playerElementMap[$element]);

                try {
                    $this->insertPlayerPerformances($gwReader, $playerId, $seasonId);
                    if ($history !== null) {
                        $this->insertPlayerHistories($history, $playerId, $seasonNameIdMap);
                    }
                } catch (Exception $e) {
                    $this->logger->error('Failed to insert performance or history', ['element' => $element, 'playerId' => $playerId, 'exception' => $e]);
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
        $columnsString = implode(',', array_merge(array_keys(self::HEADERS), ['player_id', 'fixture_id', 'opponent_team_id', 'team_id']));
        $sql = 'INSERT INTO player_performances (' . $columnsString . ') VALUES';

        $inserts = [];
        $values = [];
        foreach ($reader as $row) {
            [$fixtureId, $awayTeamId, $homeTeamId] = $this->getFixtureGlobalId($seasonId, $row['fixture']);
            $rowInserts = [];
            foreach (self::HEADERS as $header => $type) {
                $extractData = $this->extractData($type, $row[$header] ?? null);
                $values[] = $extractData;
                $rowInserts[] = '?';
            }
            $wasHome = $this->extractData('bool', $row['was_home']);

            $values[] = $playerId;
            $values[] = $fixtureId;
            $values[] = $wasHome ? $awayTeamId : $homeTeamId;
            $values[] = $wasHome ? $homeTeamId : $awayTeamId;
            $rowInserts[] = '?';
            $rowInserts[] = '?';
            $rowInserts[] = '?';
            $rowInserts[] = '?';
            $inserts[] = '(' . implode(',', $rowInserts) . ')';
        }

        $sql .= implode(',', $inserts);

        $statement = $this->pdo->prepare($sql);
        $statement->execute($values);
    }

    /**
     * @param array $history
     * @param int $playerId
     * @param array $seasonNameIdMap
     *
     * @return void
     */
    protected function insertPlayerHistories(array $history, int $playerId, array $seasonNameIdMap): void
    {
        $columnsString = implode(',', array_merge(array_keys(self::HISTORY_HEADERS), ['player_id', 'season_id']));
        $sql = 'INSERT INTO player_histories (' . $columnsString . ') VALUES';

        $inserts = [];
        $values = [];
        foreach ($history as $row) {
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

    private function getFixtureGlobalId(int $seasonId, int $localFixture): array
    {
        if (!isset($this->fixtureIdMap)) {
            $this->fixtureIdMap = $this->buildFixtureMap();
        }

        return $this->fixtureIdMap[$seasonId][$localFixture];
    }

    private function buildFixtureMap(): array
    {
        $sql = <<<SQL
SELECT season_id, fixture, fixture_id, away_team_id, home_team_id
FROM fixtures
SQL;

        $statement = $this->pdo->prepare($sql);
        $statement->execute();

        $map = [];
        foreach ($statement->fetchAll() as [$seasonId, $fixture, $fixtureId, $awayTeamId, $homeTeamId]) {
            $map[$seasonId][$fixture] = [$fixtureId, $awayTeamId, $homeTeamId];
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

    /**
     * @param string $yearPlayerHistory
     *
     * @return array|null
     * @throws \League\Csv\Exception
     */
    private function readHistory(string $yearPlayerHistory): ?array
    {
        if (is_file($yearPlayerHistory)) {
            $historyReader = Reader::createFromPath($yearPlayerHistory);
            $historyReader->setHeaderOffset(0);
            $history = iterator_to_array($historyReader);
        } else {
            $history = null;
        }

        return $history;
    }
}
