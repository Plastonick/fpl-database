#!/bin/bash

$REPOSITORY=davidpugh/fpl-database

# check if there's a Fantasy-Premier-League dir
FANTASY_DIR=`pwd`/Fantasy-Premier-League
if [ ! -d "$FANTASY_DIR" ]; then
  git clone https://github.com/vaastav/Fantasy-Premier-League.git Fantasy-Premier-League
fi

cd Fantasy-Premier-League && git checkout master && git pull

docker compose down
docker compose up --build -d
docker compose exec hydration composer install
sleep 10 # give postgres enough time to start up
docker compose exec hydration vendor/bin/phinx migrate
docker compose exec hydration php hydrate.php

DATE_STRING=`date +"%F"`

CONTAINER_ID=`docker compose ps -q database`
docker commit $CONTAINER_ID $REPOSITORY:$DATE_STRING

docker tag $REPOSITORY:$DATE_STRING $REPOSITORY:latest

docker compose down
