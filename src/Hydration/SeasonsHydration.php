<?php

namespace Plastonick\FantasyDatabase\Hydration;

use PDO;
use Psr\Log\LoggerInterface;

use function scandir;
use function substr;

class SeasonsHydration
{
    public function __construct(private PDO $pdo, private LoggerInterface $logger)
    {
    }

    public function hydrate(string $dataPath)
    {
        $start = 2006;
        $end = 2006;

        foreach (scandir($dataPath) as $season) {
            if (in_array($season, ['.', '..'])) {
                continue;
            }

            if (!is_dir("{$dataPath}/$season")) {
                continue;
            }

            if (!preg_match('/^\d{4}-\d{2}$/', $season)) {
                continue;
            }

            $startYear = substr($season, 0, 4);

            $end = max((int) $startYear, $end);
        }

        $this->logger->info('Generating seasons', ['start' => $start, 'end' => $end]);

        $sql = 'INSERT INTO seasons (start_year, name) VALUES (?, ?)';
        $statement = $this->pdo->prepare($sql);

        foreach (range($start, $end) as $year) {
            $name = $year . '-' . substr($year + 1, 2, 2);
            $statement->execute([$year, $name]);
        }
    }
}
