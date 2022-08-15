<?php

namespace Plastonick\FantasyDatabase\Hydration\Api;

use DateTimeInterface;
use PDO;
use Plastonick\FantasyDatabase\Hydration\PlayerData;
use Plastonick\FantasyDatabase\PlayerPersistence;
use Plastonick\FPLClient\Entity\Fixture;
use Plastonick\FPLClient\Entity\Player;
use Plastonick\FPLClient\Transport\Client;
use Psr\Log\LoggerInterface;

class PlayerPerformanceHydration
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
        foreach ($this->client->getAllPlayers() as $player) {
            $this->persistPlayer($player);
        }
    }

    private function persistPlayer(Player $player): void
    {
        $persistence = new PlayerPersistence($this->pdo, $this->seasonId, $this->logger);
        $playerId = $persistence->matchPlayer(
            $player->getId(),
            new PlayerData(
                $player->getFirstName(),
                $player->getSecondName(),
                $player->getWebName(),
                $player->getCode(),
                $player->getElementType(),
                $this->getGlobalTeamId($player->getTeamId())
            )
        );

        $sql = <<<SQL
INSERT INTO player_performances (assists, attempted_passes, big_chances_created, big_chances_missed, bonus, bps, clean_sheets, clearances_blocks_interceptions, completed_passes, creativity, dribbles, ea_index, element, errors_leading_to_goal, errors_leading_to_goal_attempt, fixture, fouls, goals_conceded, goals_scored, ict_index, id, influence, key_passes, kickoff_time, kickoff_time_formatted, loaned_in, loaned_out, minutes, offside, open_play_crosses, own_goals, penalties_conceded, penalties_missed, penalties_saved, recoveries, red_cards, round, saves, selected, tackled, tackles, target_missed, team_a_score, team_h_score, threat, total_points, transfers_balance, transfers_in, transfers_out, value, was_home, winning_goals, yellow_cards, player_id, fixture_id, team_id, opponent_team_id)
VALUES (:assists, :attempted_passes, :big_chances_created, :big_chances_missed, :bonus, :bps, :clean_sheets, :clearances_blocks_interceptions, :completed_passes, :creativity, :dribbles, :ea_index, :element, :errors_leading_to_goal, :errors_leading_to_goal_attempt, :fixture, :fouls, :goals_conceded, :goals_scored, :ict_index, :id, :influence, :key_passes, :kickoff_time, :kickoff_time_formatted, :loaned_in, :loaned_out, :minutes, :offside, :open_play_crosses, :own_goals, :penalties_conceded, :penalties_missed, :penalties_saved, :recoveries, :red_cards, :round, :saves, :selected, :tackled, :tackles, :target_missed, :team_a_score, :team_h_score, :threat, :total_points, :transfers_balance, :transfers_in, :transfers_out, :value, :was_home, :winning_goals, :yellow_cards, :player_id, :fixture_id, :team_id, :opponent_team_id)
ON CONFLICT DO UPDATE;
SQL;

        $statement = $this->pdo->prepare($sql);
        foreach ($player->getPerformances() as $performance) {
            if ($performance->getWasHome()) {
                $teamId = $this->getGlobalTeamId($player->getTeamId());
                $opponentTeamId = $this->getGlobalTeamId($performance->getOpponentTeam());
            } else {
                $teamId = 1;
                $opponentTeamId = 1;
            }

            $statement->execute([
                'assists' => $performance->getAssists(),
                'attempted_passes' => null,
                'big_chances_created' => null,
                'big_chances_missed' => null,
                'bonus' => $performance->getBonus(),
                'bps' => $performance->getBps(),
                'clean_sheets' => $performance->getCleanSheets(),
                'clearances_blocks_interceptions' => null,
                'completed_passes' => null,
                'creativity' => $performance->getCreativity(),
                'dribbles' => null,
                'ea_index' => null,
                'element' => $performance->getElement(),
                'errors_leading_to_goal' => null,
                'errors_leading_to_goal_attempt' => null,
                'fixture' => $performance->getFixture()->getId(),
                'fouls' => null,
                'goals_conceded' => $performance->getGoalsConceded(),
                'goals_scored' => $performance->getGoalsScored(),
                'ict_index' => $performance->getIctIndex(),
                'id' => null,
                'influence' => $performance->getInfluence(),
                'key_passes' => null,
                'kickoff_time' => $performance->getKickoffTime()->format(DateTimeInterface::ATOM),
                'kickoff_time_formatted' => $performance->getKickoffTime()->format(DateTimeInterface::ATOM),
                'loaned_in' => null,
                'loaned_out' => null,
                'minutes' => $performance->getMinutes(),
                'offside' => null,
                'open_play_crosses' => null,
                'own_goals' => $performance->getOwnGoals(),
                'penalties_conceded' => null,
                'penalties_missed' => $performance->getPenaltiesMissed(),
                'penalties_saved' => $performance->getPenaltiesSaved(),
                'recoveries' => null,
                'red_cards' => $performance->getRedCards(),
                'round' => $performance->getRound(),
                'saves' => $performance->getSaves(),
                'selected' => $performance->getSelected(),
                'tackled' => null,
                'tackles' => null,
                'target_missed' => null,
                'team_a_score' => $performance->getAwayTeamScore(),
                'team_h_score' => $performance->getHomeTeamScore(),
                'threat' => $performance->getThreat(),
                'total_points' => $performance->getTotalPoints(),
                'transfers_balance' => $performance->getTransfersBalance(),
                'transfers_in' => $performance->getTransfersIn(),
                'transfers_out' => $performance->getTransfersOut(),
                'value' => $performance->getValue(),
                'was_home' => $performance->getWasHome(),
                'winning_goals' => null,
                'yellow_cards' => $performance->getYellowCards(),
                'player_id' => $playerId,
                'fixture_id' => $this->getGlobalFixtureId($performance->getFixture()->getId(), $this->seasonId),
                'team_id' => $teamId,
                'opponent_team_id' => $opponentTeamId,
            ]);
        }
    }
}
