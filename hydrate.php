<?php

use Plastonick\FantasyDatabase\Hydration\PlayerGameWeekHydration;

include 'vendor/autoload.php';

$connection = new \PDO('pgsql:host=192.168.1.151;port=5433;dbname=postgres', 'postgres', 'postgres');

$playerGameWeek = new PlayerGameWeekHydration($connection);
$playerGameWeek->hydrate(__DIR__ . '/Fantasy-Premier-League/data/');

// grab all data
// hydrate to the database
