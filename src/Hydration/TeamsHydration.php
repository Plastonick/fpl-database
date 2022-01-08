<?php

namespace Plastonick\FantasyDatabase\Hydration;

use League\Csv\Reader;
use PDO;
use Psr\Log\LoggerInterface;

use function is_file;

class TeamsHydration
{
    public function __construct(private PDO $pdo, private LoggerInterface $logger)
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

        $visited = [];
        foreach ($reader as $row) {
            $teamName = $row['team_name'];
            if (isset($visited[$teamName])) {
                continue;
            }

            $this->logger->info('Creating team', ['teamName' => $teamName]);
            $statement->execute([$teamName]);
            $visited[$teamName] = true;
        }

    }
}
