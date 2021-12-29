<?php

namespace Plastonick\FantasyDatabase\Hydration;

use League\Csv\Reader;
use PDO;
use function array_keys;
use function implode;
use function in_array;
use function is_dir;
use function is_file;
use function scandir;

class FixturesHydration
{
    const HEADERS = [
        'code' => 'integer',
        'event' => 'integer', # game week id
        'finished' => 'bool',
        'finished_provisional' => 'bool',
        'id' => 'integer',
        'kickoff_time' => 'datetime',
        'minutes' => 'integer',
        'provisional_start_time' => 'bool',
        'started' => 'bool',
        'team_a' => 'integer',
        'team_a_score' => 'integer',
        'team_h' => 'integer',
        'team_h_score' => 'integer',
        'stats' => 'json',
        'team_h_difficulty' => 'integer',
        'team_a_difficulty' => 'integer',
        'pulse_id' => 'integer',
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

            $fixturesPath = "{$dataPath}/{$year}/fixtures.csv";
            if (!is_file($fixturesPath)) {
                continue;
            }

            $reader = Reader::createFromPath($fixturesPath);
            $reader->setHeaderOffset(0);

            $sql = 'INSERT INTO game_weeks (' . implode(',', array_keys(self::HEADERS)) . ') VALUES';

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
