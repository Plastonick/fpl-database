<?php

namespace Plastonick\FantasyDatabase\Hydration;

use PDO;
use function scandir;
use function substr;

class SeasonsHydration
{
    public function __construct(private PDO $pdo)
    {
    }

    public function hydrate(string $dataPath)
    {
        $start = 2006;
        $end = 2006;

        foreach (scandir($dataPath) as $year) {
            if (in_array($year, ['.', '..'])) {
                continue;
            }

            if (!is_dir("{$dataPath}/$year")) {
                continue;
            }

            if (!preg_match('/^\d{4}-\d{2}$/', $year)) {
                continue;
            }

            $startYear = substr($year, 0, 4);

            $end = max((int) $startYear, $end);
        }

        $sql = 'INSERT INTO seasons (start_year, name) VALUES (?, ?)';
        $statement = $this->pdo->prepare($sql);

        foreach (range($start, $end) as $year) {
            $name = $year . '-' . substr($year + 1, 2, 2);
            $statement->execute([$year, $name]);
        }
    }
}
