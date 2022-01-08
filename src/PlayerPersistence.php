<?php

namespace Plastonick\FantasyDatabase;

use Exception;
use PDO;
use Psr\Log\LoggerInterface;

class PlayerPersistence
{
    private array $playerIdCache = [];

    public function __construct(
        private PDO $pdo,
        private int $seasonId,
        private LoggerInterface $logger
    ) {
    }

    public function matchPlayer(int $element, array $playerData): int
    {
        if (!isset($this->playerIdCache[$element])) {
            $playerId = $this->getOrCreatePlayer($element, $playerData);
            $this->playerIdCache[$element] = $playerId;

            [,,,, $elementType] = $playerData[$element];
            $this->cachePlayerSeasonPosition($playerId, $elementType);
        }


        return $this->playerIdCache[$element];
    }

    /**
     * @param int $element
     * @param array $playerData
     *
     * @return int
     * @throws Exception
     */
    private function getOrCreatePlayer(int $element, array $playerData): int
    {
        [$firstName, $secondName, $webName, $elementCode, ] = $playerData[$element];

        // check if there's an existing history for this player, return if so
        if ($playerId = $this->getPlayerIdFromElementCode($elementCode)) {
            return $playerId;
        }

        return $this->createPlayer($firstName, $secondName, $webName, $elementCode);
    }

    private function createPlayer(
        string $firstName,
        string $secondName,
        string $webName,
        string $elementCode
    ): int {
        $sql = 'INSERT INTO players (first_name, second_name, web_name, element_code) VALUES (?, ?, ?, ?)';
        $statement = $this->pdo->prepare($sql);
        $statement->execute([$firstName, $secondName, $webName, $elementCode]);

        $playerId = $this->getPlayerIdFromElementCode($elementCode);
        if (!$playerId) {
            throw new Exception('Failed to retrieve created player by element code ' . $elementCode);
        }

        $this->logger->debug(
            'Created new player',
            [
                'playerId' => $playerId,
                'elementCode' => $elementCode,
                'firstName' => $firstName,
                'secondName' => $secondName,
                'webName' => $webName,
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
}
