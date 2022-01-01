<?php

namespace Plastonick\FantasyDatabase\Hydration;

use DateTime;
use League\Csv\Reader;
use PDO;
use function scandir;

class GameWeekHydration
{
    public function __construct(private PDO $pdo)
    {
    }

    /**
     * @param string $dataPath
     *
     * @return void
     * @throws \League\Csv\Exception
     */
    public function hydrate(string $dataPath)
    {
        $seasonNameIdMap = $this->getSeasonNameIdMap();

        foreach (scandir($dataPath) as $year) {
            if (in_array($year, ['.', '..'])) {
                continue;
            }

            $seasonId = $seasonNameIdMap[$year] ?? null;

            if (!$seasonId) {
                continue;
            }

            $mergedGameWeeksPath = "{$dataPath}/{$year}/gws/merged_gw.csv";

            if (!is_file($mergedGameWeeksPath)) {
                throw new \Exception('Failed to find merged gameweeks file');
            }

            $reader = Reader::createFromPath($mergedGameWeeksPath);
            $reader->setHeaderOffset(0);

            $gwIndexStartMap = [];
            foreach ($reader as $row) {
                $gwNumber = $row['GW'];
                $fixtureKickOff = DateTime::createFromFormat(DateTime::ATOM, $row['kickoff_time']);

                if (!isset($gwIndexStartMap[$gwNumber])) {
                    $gwIndexStartMap[$gwNumber] = $fixtureKickOff;
                }

                if ($gwIndexStartMap[$gwNumber]->getTimestamp() > $fixtureKickOff->getTimestamp()) {
                    $gwIndexStartMap[$gwNumber] = $fixtureKickOff;
                }
            }

            ksort($gwIndexStartMap);

            foreach ($gwIndexStartMap as $gwIndex => $gameWeekStart) {
                $sql = 'INSERT INTO game_weeks (start, event, season_id) VALUES (?, ?, ?)';

                $statement = $this->pdo->prepare($sql);
                $statement->execute([$gameWeekStart->format(DateTime::ATOM), $gwIndex, $seasonId]);
            }
        }
    }

    private function getSeasonNameIdMap()
    {
        $sql = <<<SQL
SELECT season_id, name FROM seasons
SQL;

        $statement = $this->pdo->prepare($sql);
        $statement->execute();
        $results = $statement->fetchAll();

        $map = [];
        foreach ($results as [$seasonId, $name]) {
            $map[$name] = $seasonId;
        }

        return $map;
    }
}
