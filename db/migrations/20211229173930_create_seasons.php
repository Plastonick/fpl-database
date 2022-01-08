<?php
declare(strict_types=1);

use Phinx\Db\Adapter\PostgresAdapter;
use Phinx\Migration\AbstractMigration;
use Plastonick\FantasyDatabase\Migration\HelperTrait;

final class CreateSeasons extends AbstractMigration
{
    use HelperTrait;

    public function up(): void
    {
        $seasons = $this->createTable('seasons', 'season_id');
        $seasons->addColumn('start_year', PostgresAdapter::PHINX_TYPE_INTEGER, ['null' => false]);
        $seasons->addColumn('name', PostgresAdapter::PHINX_TYPE_STRING, ['null' => false]);

        $seasons->addIndex(['name'], ['unique' => true]);
        $seasons->addIndex(['start_year']);

        $seasons->save();
    }
}
