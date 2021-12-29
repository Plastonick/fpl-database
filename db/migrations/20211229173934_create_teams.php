<?php
declare(strict_types=1);

use Phinx\Db\Adapter\PostgresAdapter;
use Phinx\Migration\AbstractMigration;
use Plastonick\FantasyDatabase\Migration\HelperTrait;

final class CreateTeams extends AbstractMigration
{
    use HelperTrait;

    public function up(): void
    {
        $teams = $this->createTable('teams', 'team_id');
        $teams->addColumn('name', PostgresAdapter::PHINX_TYPE_STRING);
        $teams->addIndex('name', ['unique' => true]);
        $teams->save();
    }
}
