<?php

namespace Plastonick\FantasyDatabase\Hydration;

use League\Csv\Reader;
use PDO;

class PlayerPeformanceHydration
{
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

                $reader = Reader::createFromPath($yearPlayerGw);
                $reader->setHeaderOffset(0);

                $sql = 'INSERT INTO player_performances (' . implode(',', array_keys(self::HEADERS)) . ') VALUES';

                $inserts = [];
                $values = [];
                foreach ($reader as $row) {
                    $rowInserts = [];
                    foreach (self::HEADERS as $header => $type) {
                        $extractData = $this->extractData($type, $row[$header] ?? null);
                        $values[] = $extractData;
                        $rowInserts[] = '?';
                    }
                    $inserts[] = '(' . implode(',', $rowInserts) . ')';
                }

                $sql .= implode(',', $inserts);

                $statement = $this->pdo->prepare($sql);
                $statement->execute($values);
            }
        }
    }


    private function extractData(string $type, ?string $raw)
    {
        if ($raw === null) {
            return null;
        }

        return match ($type) {
            'integer' => (int) $raw,
            'bool' => in_array(strtolower($raw), ['true', '1', 'y']) ? 1 : 0,
            default => $raw
        };
    }
}
