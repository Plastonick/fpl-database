<?php

namespace Plastonick\FantasyDatabase\Hydration\Api;

use PDO;
use Plastonick\FPLClient\Transport\Client;

trait DataMapTrait
{
    private readonly PDO $pdo;
    private readonly Client $client;

    private function buildTeamIdMap()
    {
        $sql = <<<SQL
SELECT team_id, name
FROM teams
SQL;

        $statement = $this->pdo->prepare($sql);
        $statement->execute();
        $results = $statement->fetchAll();

        $seasonTeamsByName = [];
        $seasonTeams = $this->client->getAllTeams();
        foreach ($seasonTeams as $seasonTeam) {
            $this->logger->info('Hydrating team', ['team' => $seasonTeam->getName()]);
            $seasonTeamsByName[$seasonTeam->getName()] = $seasonTeam;
        }

        $map = [];
        foreach ($results as [$globalTeamId, $name]) {
            $seasonTeam = $seasonTeamsByName[$name] ?? null;

            if ($seasonTeam) {
                $map[$seasonTeam->getId()] = $globalTeamId;
            }
        }

        return $map;
    }

    private function getGlobalTeamId(int $seasonTeamId): int
    {
        static $teamIdMap = null;

        if ($teamIdMap === null) {
            $teamIdMap = $this->buildTeamIdMap();
        }

        return $teamIdMap[$seasonTeamId];
    }

    private function getGlobalFixtureId(int $fixture, int $seasonId): int
    {
        static $fixtureMap = [];

        if (!isset($fixtureMap[$seasonId])) {
            $sql = <<<SQL
SELECT fixture_id, fixture FROM fixtures WHERE season_id = :seasonId
SQL;

            $statement = $this->pdo->prepare($sql);
            $statement->execute(['seasonId' => $seasonId]);

            $results = $statement->fetchAll();
            foreach ($results as [$globalFixtureId, $fixture]) {
                $fixtureMap[$seasonId][$fixture] = $globalFixtureId;
            }
        }

        return $fixtureMap[$seasonId][$fixture];
    }
}
