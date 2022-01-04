<?php

use Plastonick\FantasyDatabase\Hydration\FixturesHydration;
use Plastonick\FantasyDatabase\Hydration\GameWeekHydration;
use Plastonick\FantasyDatabase\Hydration\PlayerPeformanceHydration;
use Plastonick\FantasyDatabase\Hydration\SeasonsHydration;
use Plastonick\FantasyDatabase\Hydration\TeamsHydration;

include 'vendor/autoload.php';

$connection = new \PDO("pgsql:host={$_ENV['DB_HOST']};port=5432;dbname={$_ENV['DB_NAME']}", $_ENV['DB_USER'], $_ENV['DB_PASS']);

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
