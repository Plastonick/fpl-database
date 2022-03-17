<?php

namespace Plastonick\FantasyDatabase;

use Exception;
use PDO;
use Plastonick\FantasyDatabase\Hydration\PlayerData;
use Psr\Log\LoggerInterface;
use function count;

class PlayerPersistence
{
    private array $playerIdCache = [];

    public function __construct(
        private PDO $pdo,
        private int $seasonId,
        private LoggerInterface $logger
    ) {
    }

    public function matchPlayer(int $element, PlayerData $playerData): int
    {
        if (!isset($this->playerIdCache[$element])) {
            $playerId = $this->getOrCreatePlayer($playerData);
            $this->playerIdCache[$element] = $playerId;

            $this->cachePlayerSeasonPosition($playerId, $playerData->elementType);
        }


        return $this->playerIdCache[$element];
    }

    /**
     * @param PlayerData $playerData
     *
     * @return int
     * @throws Exception
     */
    private function getOrCreatePlayer(PlayerData $playerData): int
    {
        // check if there's an existing history for this player, return if so
        if ($playerId = $this->getPlayerIdFromElementCode($playerData->elementCode)) {
            // the player may have moved team, update their team ID
            $this->updatePlayerTeamId($playerId, $playerData->teamId);

            return $playerId;
        }

        return $this->createPlayer($playerData);
    }

    private function createPlayer(PlayerData $playerData): int
    {
        $sql = 'INSERT INTO players (first_name, second_name, web_name, element_code, last_team_id) VALUES (?, ?, ?, ?, ?)';
        $statement = $this->pdo->prepare($sql);
        $statement->execute(
            [
                $playerData->firstName,
                $playerData->secondName,
                $playerData->webName,
                $playerData->elementCode,
                $playerData->teamId,
            ]
        );

        $playerId = $this->getPlayerIdFromElementCode($playerData->elementCode);
        if (!$playerId) {
            throw new Exception('Failed to retrieve created player by element code ' . $playerData->elementCode);
        }

        $this->logger->debug(
            'Created new player',
            [
                'playerId' => $playerId,
                'elementCode' => $playerData->elementCode,
                'firstName' => $playerData->firstName,
                'secondName' => $playerData->secondName,
                'webName' => $playerData->webName,
            ]
        );

        return $playerId;
    }

    private function cachePlayerSeasonPosition(int $playerId, int $positionId)
    {
        $sql = 'INSERT INTO player_season_positions (player_id, season_id, position_id) VALUES (?, ?, ?)';

        $statement = $this->pdo->prepare($sql);
        $statement->execute([$playerId, $this->seasonId, $positionId]);
    }

    private function getPlayerIdFromElementCode(int $elementCode): ?int
    {
        $sql = <<<SQL
select players.player_id
from players
where players.element_code = ?
SQL;

        $statement = $this->pdo->prepare($sql);
        $statement->execute([$elementCode]);

        $matchingPlayerIds = $statement->fetchAll();
        if (count($matchingPlayerIds) > 1) {
            throw new Exception("Failed to retrieve unique player ID by element code {$elementCode}");
        }

        return $matchingPlayerIds[0][0] ?? null;
    }

    private function updatePlayerTeamId(int $playerId, int $teamId): void
    {
        $sql = <<<SQL
UPDATE players SET last_team_id = ? WHERE player_id = ?
SQL;

        $statement = $this->pdo->prepare($sql);
        $statement->execute([$teamId, $playerId]);
    }
}
