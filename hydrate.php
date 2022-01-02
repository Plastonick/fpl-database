<?php

use Plastonick\FantasyDatabase\Hydration\FixturesHydration;
use Plastonick\FantasyDatabase\Hydration\GameWeekHydration;
use Plastonick\FantasyDatabase\Hydration\PlayerPeformanceHydration;
use Plastonick\FantasyDatabase\Hydration\SeasonsHydration;
use Plastonick\FantasyDatabase\Hydration\TeamsHydration;

include 'vendor/autoload.php';

//$connection = new \PDO('pgsql:host=192.168.1.151;port=5433;dbname=postgres', 'postgres', 'postgres');
$connection = new \PDO('pgsql:host=database;port=5432;dbname=postgres', 'postgres', 'postgres');

$hydration = new SeasonsHydration($connection);
$hydration->hydrate(__DIR__ . '/Fantasy-Premier-League/data/');

$hydration = new TeamsHydration($connection);
$hydration->hydrate(__DIR__ . '/Fantasy-Premier-League/data/');

$hydration = new GameWeekHydration($connection);
$hydration->hydrate(__DIR__ . '/Fantasy-Premier-League/data/');

$hydration = new FixturesHydration($connection);
$hydration->hydrate(__DIR__ . '/Fantasy-Premier-League/data/');

$playerGameWeek = new PlayerPeformanceHydration($connection);
$playerGameWeek->hydrate(__DIR__ . '/Fantasy-Premier-League/data/');

// grab all data
// hydrate to the database
