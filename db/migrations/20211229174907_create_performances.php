<?php
declare(strict_types=1);

use Phinx\Db\Adapter\PostgresAdapter;
use Phinx\Migration\AbstractMigration;
use Plastonick\FantasyDatabase\Hydration\PlayerPeformanceHydration;
use Plastonick\FantasyDatabase\Migration\HelperTrait;

final class CreatePerformances extends AbstractMigration
{
    use HelperTrait;

    public function up(): void
    {
        $table = $this->createTable('player_performances', 'player_performance_id');

        foreach (PlayerPeformanceHydration::HEADERS as $header => $type) {
            $table = $this->addColumn($table, $header, $type);
        }

        $table->addColumn('player_id', PostgresAdapter::PHINX_TYPE_BIG_INTEGER);
        $table->addForeignKey(['player_id'], 'players', ['player_id']);

        $table->addColumn('fixture_id', PostgresAdapter::PHINX_TYPE_BIG_INTEGER);
        $table->addForeignKey(['fixture_id'], 'fixtures', ['fixture_id']);

        // TODO, add once players have better uniqueness
//        $table->addIndex(['player_id', 'fixture_id'], ['unique' => true]);

        $table->save();
    }
}
