<?php
declare(strict_types=1);

use Phinx\Db\Adapter\PostgresAdapter;
use Phinx\Db\Table;
use Phinx\Migration\AbstractMigration;
use Plastonick\FantasyDatabase\Hydration\FixturesHydration;
use Plastonick\FantasyDatabase\Hydration\PlayerGameWeekHydration;

final class CreateFantasyTables extends AbstractMigration
{
    public function change(): void
    {
        $playerGameWeeks = $this->createTable('player_game_weeks', 'player_game_week_id');
        foreach (PlayerGameWeekHydration::HEADERS as $header => $type) {
            $playerGameWeeks = $this->addColumn($playerGameWeeks, $header, $type);
        }
        $playerGameWeeks->save();

        $seasons = $this->createTable('seasons', 'season_id');
        $seasons->addColumn('start_year', PostgresAdapter::PHINX_TYPE_INTEGER, ['null' => false]);
        $seasons->save();

        $teams = $this->createTable('teams', 'team_id');
        $teams->addColumn('name');
        $teams->addIndex('name');

        $gameWeek = $this->createTable('game_weeks', 'game_week_id');
        $gameWeek->addColumn('start', PostgresAdapter::PHINX_TYPE_TIMESTAMP);
        $gameWeek->addColumn('season_id', PostgresAdapter::PHINX_TYPE_BIG_INTEGER);
        $gameWeek->addForeignKey(['season_id'], $seasons->getTable(), ['season_id']);

        $fixtures = $this->createTable('fixtures', 'fixture_id');
        foreach (FixturesHydration::HEADERS as $header => $type) {
            $fixtures = $this->addColumn($fixtures, $header, $type);
        }
        $fixtures->save();
    }

    private function addColumn(Table $table, string $name, string $type): Table
    {
        $phinxType = match ($type) {
            'integer' => PostgresAdapter::PHINX_TYPE_INTEGER,
            'string' => PostgresAdapter::PHINX_TYPE_STRING,
            'datetime' => PostgresAdapter::PHINX_TYPE_DATETIME,
            'decimal' => PostgresAdapter::PHINX_TYPE_DECIMAL,
            'bool' => PostgresAdapter::PHINX_TYPE_BOOLEAN,
        };

        $options = ['null' => true];

        $table->addColumn($name, $phinxType, $options);

        return $table;
    }


    /**
     * @param string $tableName
     * @param string $pkName
     * @param array|null $options
     *
     * @return Table
     */
    private function createTable(string $tableName, string $pkName, ?array $options = [])
    {
        if (!isset($options['collation'])) {
            $options['collation'] = 'utf8mb4_unicode_ci';
        }

        $table = parent::table($tableName, $options);
        $table->create();

        $table->removeColumn('id');
        $table->addColumn(
            $pkName,
            PostgresAdapter::PHINX_TYPE_BIG_INTEGER,
            ['signed' => false, 'identity' => true]
        );
        $table->save();

        return $table;
    }
}
