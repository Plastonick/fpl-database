#!/bin/bash

docker-compose down
docker-compose up -d
docker-compose exec hydration composer install
sleep 10 # give postgres enough time to start up
docker-compose exec hydration vendor/bin/phinx migrate
docker-compose exec hydration php hydrate.php

# snapshot psql image

#docker-compose down
