<?php
declare(strict_types=1);

use Phinx\Db\Adapter\PostgresAdapter;
use Phinx\Migration\AbstractMigration;
use Plastonick\FantasyDatabase\Migration\HelperTrait;

final class CreatePlayers extends AbstractMigration
{
    use HelperTrait;

    public function up(): void
    {
        $table = $this->createTable('players', 'player_id');

        $table->addColumn('first_name', PostgresAdapter::PHINX_TYPE_STRING);
        $table->addColumn('second_name', PostgresAdapter::PHINX_TYPE_STRING);

        $table->addIndex(['first_name']);
        $table->addIndex(['second_name']);

        $table->save();
    }
}
