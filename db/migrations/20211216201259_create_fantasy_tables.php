<?php
declare(strict_types=1);

use Phinx\Db\Adapter\PostgresAdapter;
use Phinx\Migration\AbstractMigration;

final class CreateFantasyTables extends AbstractMigration
{
    public function change(): void
    {
        $this->table('players')
            ->addColumn('player_id', PostgresAdapter::PHINX_TYPE_BIG_INTEGER)
            ->save();
    }
}
