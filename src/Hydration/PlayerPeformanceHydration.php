<?php

namespace Plastonick\FantasyDatabase\Hydration;

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

    /** @var int[] */
    private array $playerIdMap = [];

    /** @var int[] */
    private array $fixtureIdMap = [];

    public function __construct(private PDO $pdo)
    {
    }

    public function hydrate(string $dataPath)
    {
        foreach (scandir($dataPath) as $year) {
            if (in_array($year, ['.', '..'])) {
                continue;
            }

            $yearPlayersPath = "{$dataPath}/{$year}/players/";
            if (!is_dir($yearPlayersPath)) {
                continue;
            }

            foreach (scandir($yearPlayersPath) as $player) {
                if (in_array($player, ['.', '..'])) {
                    continue;
                }

                $yearPlayerGw = "{$yearPlayersPath}/{$player}/gw.csv";

                if (!is_file($yearPlayerGw)) {
                    echo "Uh oh!\n";
                    continue;
                }

                preg_match('/^([^\d\s_]+)(?:(?:[_\s])*.*[_\s])([^\d\s_]+)(_\d+)?$/', $player, $matches);
                $firstName = $matches[1];
                $secondName = $matches[2];
                $elementId = $matches[3] ?? null;
                $playerId = $this->getPlayerGlobalId($firstName, $secondName);

                $reader = Reader::createFromPath($yearPlayerGw);
                $reader->setHeaderOffset(0);

                $columnsString = implode(',', array_merge(array_keys(self::HEADERS), ['player_id', 'fixture_id']));
                $sql = 'INSERT INTO player_performances (' . $columnsString . ') VALUES';

                $inserts = [];
                $values = [];
                foreach ($reader as $row) {
                    $rowInserts = [];
                    foreach (self::HEADERS as $header => $type) {
                        $extractData = $this->extractData($type, $row[$header] ?? null);
                        $values[] = $extractData;
                        $rowInserts[] = '?';
                    }
                    $values[] = $playerId;
                    $values[] = $this->getFixtureGlobalId($year, $row['round'], $row);
                    $rowInserts[] = '?';
                    $rowInserts[] = '?';
                    $inserts[] = '(' . implode(',', $rowInserts) . ')';
                }

                $sql .= implode(',', $inserts);

                $statement = $this->pdo->prepare($sql);
                $statement->execute($values);
            }
        }
    }

    private function getPlayerGlobalId($firstName, $secondName): int
    {
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

    private function getFixtureGlobalId(string $seasonName, int $round, array $data): int
    {
        $key = $seasonName . '~' . $round;

        if (!isset($this->fixtureIdMap[$key])) {
            $columns = [
                'round',
                'kickoff_time',
                'team_a_score',
                'team_h_score',
                'team_h_difficulty',
                'team_a_difficulty',
            ];
            $columnsString = implode(',', $columns);
            $paramsString = implode(',', array_fill(0, count($columns), '?'));
            $sql = "INSERT INTO fixtures ({$columnsString}) VALUES ({$paramsString})";
            $statement = $this->pdo->prepare($sql);

            $statement->execute([
                $round,
                $data['kickoff_time'],
                $data['team_a_score'],
                $data['team_h_score'],
                $data['kickoff_time'],
                $data['kickoff_time'],
                $data['kickoff_time'],
            ]);
        }

        return $this->fixtureIdMap[$key];
    }
}
