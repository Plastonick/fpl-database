<?php

namespace Plastonick\FantasyDatabase\Hydration\Api;

use PDO;
use Plastonick\FPLClient\Entity\Team;
use Plastonick\FPLClient\Transport\Client;
use Psr\Log\LoggerInterface;

class MetaHydration
{
    public function __construct(
        private readonly PDO $pdo,
        private readonly Client $client,
        private readonly LoggerInterface $logger
    ) {
    }

    public function persistTeams(): void
    {
        foreach ($this->client->getAllTeams() as $team) {
            $this->persistTeam($team);
        }
    }

    public function persistSeason(int $year): int
    {
        $this->logger->info('Persisting season', ['year' => $year]);

        $sql = <<<SQL
INSERT INTO seasons (start_year, name)
VALUES (:startYear, :name)
ON CONFLICT DO NOTHING;
SQL;

        $name = "{$year}-" . ($year - 1999);

        $statement = $this->pdo->prepare($sql);
        $statement->execute(['startYear' => (string) $year, 'name' => $name]);

        $sql = 'SELECT season_id FROM seasons WHERE start_year = :startYear';
        $statement = $this->pdo->prepare($sql);
        $statement->execute(['startYear' => (string) $year]);
        $results = $statement->fetchAll();

        return $results[0][0];
    }

    private function persistTeam(Team $team): void
    {
        $this->logger->info('Persisting team', ['team' => $team->getName()]);

        $sql = <<<SQL
INSERT INTO teams (name)
VALUES (:team)
ON CONFLICT DO NOTHING;
SQL;

        $statement = $this->pdo->prepare($sql);
        $statement->execute(['team' => $team->getName()]);
    }
}
