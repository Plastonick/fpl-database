## WIP

### Fantasy Premier League Database

This project creates a pre-hydrated database with data taken from [vaastav/Fantasy-Premier-League](https://github.com/vaastav/Fantasy-Premier-League). Allows for reading data for other projects without needing to parse the [vaastav/Fantasy-Premier-League](https://github.com/vaastav/Fantasy-Premier-League) file system. This links teams and players across seasons, so inter-seasonal trends can more easily be established. 


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

### Example

Find the top 5 scoring players in a single game week:

```sql
select s.name,
       p.first_name,
       p.second_name,
       sum(pp.total_points)   as game_week_points,
       count(gw.game_week_id) as num_fixtures
from game_weeks gw
         inner join fixtures f on gw.game_week_id = f.game_week_id
         inner join player_performances pp on f.fixture_id = pp.fixture_id
         inner join players p on pp.player_id = p.player_id
         inner join seasons s on f.season_id = s.season_id
group by gw.game_week_id, s.name, p.first_name, p.second_name
order by game_week_points desc
limit 5;
```


| name | first\_name | second\_name | game\_week\_points | num\_fixtures |
| :--- | :--- | :--- | :--- | :--- |
| 2016-17 | Harry | Kane | 31 | 2 |
| 2017-18 | Mohamed | Salah | 29 | 1 |
| 2016-17 | Alexis | Sánchez | 27 | 2 |
| 2020-21 | John | Stones | 27 | 2 |
| 2019-20 | Michail | Antonio | 26 | 1 |

