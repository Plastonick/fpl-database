<?php

namespace Plastonick\FantasyDatabase;

use Exception;
use PDO;

class PlayerPersistence
{
    private array $playerIdCache = [];

    public function __construct(private PDO $pdo, private array $nameElementMap)
    {
    }

    public function matchPlayer(int $element, array $history): int
    {
        if (!isset($this->playerIdCache[$element])) {
            $this->playerIdCache[$element] = $this->getOrCreatePlayerId($element, $history);
        }


        return $this->playerIdCache[$element];
    }

    private function getOrCreatePlayerId(int $element, array $history): int
    {
        [$firstName, $secondName] = $this->nameElementMap[$element];

        // our mystery player has no history, we cannot match them; so we must create them
        if (count($history) === 0) {
            return $this->createPlayer($firstName, $secondName);
        }

        $elementCode = (int) $history[array_key_first($history)]['element_code'];

        // so, a player already exists, we need to compare histories now!
        $playerId = $this->getPlayerIdFromElementCode($elementCode);

        // there's no history to match this player to, create a new player
        if ($playerId === null) {
            return $this->createPlayer($firstName, $secondName);
        } else {
            return $playerId;
        }
    }

    private function createPlayer(string $firstName, string $secondName): int
    {
        $sql = 'INSERT INTO players (first_name, second_name) VALUES (?, ?)';
        $statement = $this->pdo->prepare($sql);
        $statement->execute([$firstName, $secondName]);

        $statement = $this->pdo->prepare('SELECT player_id FROM players WHERE first_name = ? AND second_name = ? ORDER BY player_id DESC LIMIT 1');
        $statement->execute([$firstName, $secondName]);
        return (int) $statement->fetchColumn();
    }

    private function getPlayerIdFromElementCode(int $elementCode): ?int
    {
        $sql = <<<SQL
select distinct player_histories.player_id
from player_histories
where player_histories.element_code = ?
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
