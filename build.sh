#!/bin/bash

docker-compose up -d
docker-compose exec hydration composer install
docker-compose exec hydration vendor/bin/phinx m
docker-compose exec hydration php hydrate.php

# snapshot psql image

#docker-compose down
