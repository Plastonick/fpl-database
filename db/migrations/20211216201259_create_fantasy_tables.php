<?php
declare(strict_types=1);

use Phinx\Db\Adapter\PostgresAdapter;
use Phinx\Db\Table;
use Phinx\Migration\AbstractMigration;
use Plastonick\FantasyDatabase\Hydration\PlayerGameWeekHydration;

final class CreateFantasyTables extends AbstractMigration
{
    public function change(): void
    {
        $headers = PlayerGameWeekHydration::HEADERS;

        $table = $this->createTable('player_game_weeks', 'player_game_week_id');


        foreach ($headers as $header => $type) {
            $table = $this->addColumn($table, $header, $type);
        }

        $table->save();
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

        if ($type === 'decimal') {
        }

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
