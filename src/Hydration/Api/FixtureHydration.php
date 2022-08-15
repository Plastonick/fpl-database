<?php

namespace Plastonick\FantasyDatabase\Hydration\Api;

use DateTimeInterface;
use PDO;
use Plastonick\FPLClient\Entity\Fixture;
use Plastonick\FPLClient\Transport\Client;
use Psr\Log\LoggerInterface;

class FixtureHydration
{
    use DataMapTrait;

    public function __construct(
        private readonly int $seasonId,
        private readonly PDO $pdo,
        private readonly Client $client,
        private readonly LoggerInterface $logger
    ) {
    }

    public function hydrate(): void
    {
        foreach ($this->client->getAllFixtures() as $fixture) {
            $this->persistFixture($fixture);
        }
    }

    private function persistFixture(Fixture $fixture): void
    {
        $this->logger->info(
            'Persisting fixture',
            [
                'fixture' => $fixture->getId(),
                'season_id' => $this->seasonId,
                'away_team_id' => $this->getGlobalTeamId($fixture->getAwayTeamId()),
                'home_team_id' => $this->getGlobalTeamId($fixture->getHomeTeamId()),
            ]
        );

        $sql = <<<SQL
INSERT INTO fixtures (event, fixture, finished, finished_provisional, kickoff_time, team_a_score, team_h_score, team_h_difficulty, team_a_difficulty, season_id, away_team_id, home_team_id)
VALUES (:event, :fixture, :finished, :finished_provisional, :kickoff_time, :team_a_score, :team_h_score, :team_h_difficulty, :team_a_difficulty, :season_id, :away_team_id, :home_team_id)
ON CONFLICT DO NOTHING
SQL;

        $statement = $this->pdo->prepare($sql);
        $statement->execute([
            'event' => $fixture->getEvent(),
            'fixture' => $fixture->getId(),
            'finished' => $fixture->getFinished(),
            'finished_provisional' => $fixture->getFinishedProvisional(),
            'kickoff_time' => $fixture->getKickoffTime()->format(DateTimeInterface::ATOM),
            'team_a_score' => $fixture->getAwayTeamScore(),
            'team_h_score' => $fixture->getHomeTeamScore(),
            'team_h_difficulty' => $fixture->getTeamHDifficulty(),
            'team_a_difficulty' => $fixture->getTeamADifficulty(),
            'season_id' => $this->seasonId,
            'away_team_id' => $this->getGlobalTeamId($fixture->getAwayTeamId()),
            'home_team_id' => $this->getGlobalTeamId($fixture->getHomeTeamId()),
        ]);
    }
}
