<?php

use Plastonick\FantasyDatabase\Hydration\Api\FixtureHydration;
use Plastonick\FantasyDatabase\Hydration\Api\MetaHydration;
use Plastonick\FantasyDatabase\Hydration\Api\PlayerPerformanceHydration;
use Plastonick\FPLClient\Transport\Client;

include 'vendor/autoload.php';

$logger = new Monolog\Logger('add_current_data', [new Monolog\Handler\StreamHandler('php://stdout')]);

$connection = new \PDO("pgsql:host={$_ENV['DB_HOST']};port={$_ENV['DB_PORT']};dbname={$_ENV['DB_NAME']}", $_ENV['DB_USER'], $_ENV['DB_PASS']);

$client = Client::create();

$global = new MetaHydration($connection, $client, $logger);
$seasonId = $global->persistSeason(2022);
$global->persistTeams();

$fixtureHydration = new FixtureHydration($seasonId, $connection, $client, $logger);
$fixtureHydration->hydrate();

$playerHydration = new PlayerPerformanceHydration($seasonId, $connection, $client, $logger);
$playerHydration->hydrate();
