<?php

namespace Plastonick\FantasyDatabase\Hydration;

use League\Csv\Reader;
use PDO;
use Psr\Log\LoggerInterface;

use function scandir;
use function substr;

class GlobalHydration
{
    public function __construct(private PDO $pdo, private LoggerInterface $logger)
    {
    }

    public function hydrate(string $dataPath)
    {
        $this->createSeasons($dataPath);
        $this->createTeams($dataPath);
        $this->createPositions();
    }

    /**
     * @param string $dataPath
     *
     * @return void
     */
    private function createSeasons(string $dataPath): void
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


        $sql = 'INSERT INTO seasons (start_year, name) VALUES (?, ?)';
        $statement = $this->pdo->prepare($sql);

        foreach (range($start, $end) as $year) {
            $name = $year . '-' . substr($year + 1, 2, 2);

            $this->logger->info('Creating season', ['startYear' => $year, 'name' => $name]);
            $statement->execute([$year, $name]);
        }
    }

    private function createTeams(string $dataPath): void
    {
        $masterTeamList = "{$dataPath}/master_team_list.csv";
        if (!is_file($masterTeamList)) {
            return;
        }

        $reader = Reader::createFromPath($masterTeamList);
        $reader->setHeaderOffset(0);

        $sql = 'INSERT INTO teams (name) VALUES (?)';
        $statement = $this->pdo->prepare($sql);

        $visited = [];
        foreach ($reader as $row) {
            $teamName = $row['team_name'];
            if (isset($visited[$teamName])) {
                continue;
            }

            $this->logger->info('Creating team', ['teamName' => $teamName]);
            $statement->execute([$teamName]);
            $visited[$teamName] = true;
        }
    }

    private function createPositions(): void
    {
        $positions = [
            1 => ['Goalkeeper', 'GKP'],
            2 => ['Defender', 'DEF'],
            3 => ['Midfielder', 'MID'],
            4 => ['Forward', 'FWD']
        ];

        $sql = 'INSERT INTO positions (position_id, name, abbreviation) VALUES (?, ?, ?)';
        $statement = $this->pdo->prepare($sql);

        foreach ($positions as $id => list($name, $abbreviation)) {
            $this->logger->info(
                'Creating position',
                ['positionId' => $id, 'name' => $name, 'abbreviation' => $abbreviation]
            );

            $statement->execute([$id, $name, $abbreviation]);
        }
    }
}
