<?php

use Plastonick\FantasyDatabase\Hydration\FixturesHydration;
use Plastonick\FantasyDatabase\Hydration\GameWeekHydration;
use Plastonick\FantasyDatabase\Hydration\PlayerPeformanceHydration;
use Plastonick\FantasyDatabase\Hydration\GlobalHydration;

include 'vendor/autoload.php';

$logger = new Monolog\Logger('hydration', [new Monolog\Handler\StreamHandler('php://stdout')]);

$connection = new \PDO("pgsql:host={$_ENV['DB_HOST']};port={$_ENV['DB_PORT']};dbname={$_ENV['DB_NAME']}", $_ENV['DB_USER'], $_ENV['DB_PASS']);

$global = new GlobalHydration($connection, $logger);
$global->hydrate(__DIR__ . '/Fantasy-Premier-League/data/');

$gameWeek = new GameWeekHydration($connection, $logger);
$gameWeek->hydrate(__DIR__ . '/Fantasy-Premier-League/data/');

$fixtures = new FixturesHydration($connection, $logger);
$fixtures->hydrate(__DIR__ . '/Fantasy-Premier-League/data/');

$players = new PlayerPeformanceHydration($connection, $logger);
$players->hydrate(__DIR__ . '/Fantasy-Premier-League/data/');
