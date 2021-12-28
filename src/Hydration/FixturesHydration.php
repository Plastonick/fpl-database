<?php

namespace Plastonick\FantasyDatabase\Hydration;

use League\Csv\Reader;
use PDO;

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
}
