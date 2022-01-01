<?php

namespace Plastonick\FantasyDatabase\Migration;

use Phinx\Db\Adapter\PostgresAdapter;
use Phinx\Db\Table;
use Phinx\Util\Literal;

trait HelperTrait
{
    /**
     * Returns an instance of the <code>\Table</code> class.
     *
     * You can use this class to create and manipulate tables.
     *
     * @param string $tableName Table name
     * @param array $options Options
     * @return \Phinx\Db\Table
     */
    abstract public function table($tableName, $options);

    /**
     * @param string $tableName
     * @param string $pkName
     * @param array|null $options
     *
     * @return \Phinx\Db\Table
     */
    private function createTable(string $tableName, string $pkName, ?array $options = [])
    {
        if (!isset($options['collation'])) {
            $options['collation'] = 'utf8mb4_unicode_ci';
        }

        $table = $this->table($tableName, $options);
        $table->create();

        $table->removeColumn('id');
        $table->addColumn(
            $pkName,
            PostgresAdapter::PHINX_TYPE_BIG_INTEGER,
            ['signed' => false, 'identity' => true]
        );
        $table->addIndex($pkName, ['unique' => true]);
        $table->save();

        return $table;
    }

    private function addColumn(Table $table, string $name, string $type): Table
    {
        $phinxType = match ($type) {
            'integer' => PostgresAdapter::PHINX_TYPE_INTEGER,
            'string' => PostgresAdapter::PHINX_TYPE_STRING,
            'datetime' => Literal::from('timestamptz'),
            'decimal' => PostgresAdapter::PHINX_TYPE_DECIMAL,
            'bool' => PostgresAdapter::PHINX_TYPE_BOOLEAN,
            'json' => PostgresAdapter::PHINX_TYPE_JSON,
        };

        $options = ['null' => true];

        $table->addColumn($name, $phinxType, $options);

        return $table;
    }
}
