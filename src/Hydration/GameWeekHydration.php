<?php

namespace Plastonick\FantasyDatabase\Hydration;

use PDO;
use function ctype_digit;
use function scandir;
use function substr;

class GameWeekHydration
{
    public function __construct(private PDO $pdo)
    {
    }

    public function hydrate(string $dataPath)
    {
        foreach (scandir($dataPath) as $year) {
            if (in_array($year, ['.', '..'])) {
                continue;
            }

            $startYear = substr($year, 0, 4);

            if (!ctype_digit($startYear)) {
                continue;
            }

            $sql = 'INSERT INTO seasons (start_year) VALUES (?)';

            $statement = $this->pdo->prepare($sql);
            $statement->execute([$startYear]);
        }
    }
}
