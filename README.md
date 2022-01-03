## WIP

### Fantasy Premier League Database

This project creates a pre-hydrated database with data taken from [vaastav/Fantasy-Premier-League](https://github.com/vaastav/Fantasy-Premier-League). Allows for reading data for other projects without needing to parse the file system. This links teams and players across seasons, so inter-seasonal trends can more easily be established. 


### Known Issues

- Players with same names (e.g. Danny Ward and Ben Davies) are merged
- Home/away fixture difficulties for the 2016-17 season are not included


### Usage

#### Docker

Start and run a postgres database server listening on port 5432.

```
docker run --rm -p 5432:5432 davidpugh/fpl-database:latest
```

| Attribute | Value        |
|-----------|--------------|
| User      | fantasy-user |
| Password  | fantasy-pwd  |
| Database  | fantasy-db   |
