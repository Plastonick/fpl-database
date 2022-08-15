#!/bin/bash

REPOSITORY=davidpugh/fpl-database

# check if there's a Fantasy-Premier-League dir
FANTASY_DIR=`pwd`/Fantasy-Premier-League
if [ ! -d "$FANTASY_DIR" ]; then
  git clone https://github.com/vaastav/Fantasy-Premier-League.git Fantasy-Premier-League
fi

# checkout the version of the data shortly post 2021-22 season
cd Fantasy-Premier-League && git checkout e2728c5b3bb993c0bd5cfdcf64cbb7f51d8190b5 && git pull && cd ..

docker compose down
docker compose up --build -d
docker compose exec hydration composer install
sleep 10 # give postgres enough time to start up
docker compose exec hydration vendor/bin/phinx migrate
docker compose exec hydration php hydrate.php || exit 1

DATE_STRING=`date +"%F"`
CONTAINER_ID=`docker compose ps -q database`

echo "Exporting postgres database dump to dump-$DATE_STRING.pgsql"
docker exec $CONTAINER_ID pg_dump -U fantasy-user fantasy-db > dump-$DATE_STRING.pgsql

echo 'Committing postgres docker image'
docker commit $CONTAINER_ID $REPOSITORY:$DATE_STRING
docker tag $REPOSITORY:$DATE_STRING $REPOSITORY:latest

docker compose down
