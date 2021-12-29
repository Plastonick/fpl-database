<?php

namespace Plastonick\FantasyDatabase\Hydration;

use Exception;
use League\Csv\Reader;
use PDO;
use function array_keys;
use function implode;
use function in_array;
use function is_dir;
use function is_file;
use function scandir;

class TeamsHydration
{
    public function __construct(private PDO $pdo)
    {
    }

    public function hydrate(string $dataPath)
    {
        $masterTeamList = "{$dataPath}/master_team_list.csv";
        if (!is_file($masterTeamList)) {
            return;
        }

        $reader = Reader::createFromPath($masterTeamList);
        $reader->setHeaderOffset(0);

        $sql = 'INSERT INTO teams (name) VALUES (?)';
        $statement = $this->pdo->prepare($sql);

        foreach ($reader as $row) {
            try {
                $statement->execute([$row['team_name']]);
            } catch (Exception $e) {
                // ignore, duplicate!
            }
        }

    }
}
