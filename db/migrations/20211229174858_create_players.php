<?php
declare(strict_types=1);

use Phinx\Migration\AbstractMigration;
use Plastonick\FantasyDatabase\Migration\HelperTrait;

final class CreatePlayers extends AbstractMigration
{
    use HelperTrait;

    public function up(): void
    {
        $table = $this->createTable('players', 'player_id');

        // add columns

        $table->save();
    }
}
