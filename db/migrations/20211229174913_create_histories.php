<?php
declare(strict_types=1);

use Phinx\Db\Adapter\PostgresAdapter;
use Phinx\Migration\AbstractMigration;
use Plastonick\FantasyDatabase\Hydration\PlayerPeformanceHydration;
use Plastonick\FantasyDatabase\Migration\HelperTrait;

final class CreateHistories extends AbstractMigration
{
    use HelperTrait;

    public function up(): void
    {
        $table = $this->createTable('player_histories', 'player_history_id');

        foreach (PlayerPeformanceHydration::HISTORY_HEADERS as $header => $type) {
            $table = $this->addColumn($table, $header, $type);
        }

        $table->addColumn('player_id', PostgresAdapter::PHINX_TYPE_BIG_INTEGER);
        $table->addForeignKey(['player_id'], 'players', ['player_id']);

        $table->addColumn('season_id', PostgresAdapter::PHINX_TYPE_BIG_INTEGER);
        $table->addForeignKey(['season_id'], 'seasons', ['season_id']);

        $table->addIndex(['season_id', 'player_id'], ['unique' => true]);

        $table->save();
    }
}
