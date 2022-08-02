<?php

namespace Plastonick\FantasyDatabase\Hydration;

use League\Csv\Reader;
use PDO;

trait DataMapTrait
{
    private readonly PDO $pdo;

    /**
     * @param string $dataPath
     *
     * @return array
     * @throws \League\Csv\Exception
     */
    private function getTeamIdMaps(string $dataPath): array
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
}
