<?php

include 'vendor/autoload.php';

use League\Csv\Reader;

$base = __DIR__ . '/Fantasy-Premier-League/data/';

$headers = [];

foreach (scandir($base) as $year) {
    if (in_array($year, ['.', '..'])) {
        continue;
    }

    $yearPlayersPath = "{$base}/{$year}/players/";
    if (!is_dir($yearPlayersPath)) {
        continue;
    }

    $yearHeaders = [];

//    $fixturesPath = "{$base}/{$year}/fixtures.csv";
//    if (!is_file($fixturesPath)) {
//        continue;
//    }
//    $reader = Reader::createFromPath($fixturesPath);
//    $reader->setHeaderOffset(0);
//
//    foreach ($reader as $row) {
//        $r1 = $row;
//        break;
//    }
//
//    $yearHeaders = $reader->getHeader();

    foreach (scandir($yearPlayersPath) as $player) {
        if (in_array($player, ['.', '..'])) {
            continue;
        }

        $yearPlayerGw = "{$yearPlayersPath}/{$player}/gw.csv";

        if (!is_file($yearPlayerGw)) {
            echo "Uh oh!\n";
            continue;
        }

        $reader = Reader::createFromPath($yearPlayerGw);
        $reader->setHeaderOffset(0);

        foreach ($reader as $row) {
            $r1 = $row;
            break;
        }

        $yearHeaders = array_unique($yearHeaders + $reader->getHeader());
        break;
    }
    $headers[$year] = $yearHeaders;
}

$sharedHeaders = array_intersect(...array_values($headers));
$uniqueHeaders = array_unique(array_merge(...array_values($headers)));

echo implode("\n", $sharedHeaders) . "\n";
//echo implode("\n", $uniqueHeaders);

$a = 1;
