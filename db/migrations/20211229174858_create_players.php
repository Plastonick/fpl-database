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
        $players = $this->createTable('players', 'player_id');

        $players->addColumn('first_name', PostgresAdapter::PHINX_TYPE_STRING);
        $players->addColumn('second_name', PostgresAdapter::PHINX_TYPE_STRING);
        $players->addColumn('web_name', PostgresAdapter::PHINX_TYPE_STRING);
        $players->addColumn('element_code', PostgresAdapter::PHINX_TYPE_STRING);

        $players->addColumn('last_team_id', PostgresAdapter::PHINX_TYPE_BIG_INTEGER, ['null' => false]);
        $players->addForeignKey(['last_team_id'], 'teams', ['team_id']);

        $players->addIndex(['first_name']);
        $players->addIndex(['second_name']);
        $players->addIndex(['element_code'], ['unique' => true]);

        $players->save();
    }
}
