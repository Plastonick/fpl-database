<?php
declare(strict_types=1);

use Phinx\Db\Adapter\PostgresAdapter;
use Phinx\Migration\AbstractMigration;
use Plastonick\FantasyDatabase\Migration\HelperTrait;

final class CreateGameWeeks extends AbstractMigration
{
    use HelperTrait;

    public function up(): void
    {
        $gameWeek = $this->createTable('game_weeks', 'game_week_id');
        $gameWeek->addColumn('start', PostgresAdapter::PHINX_TYPE_TIMESTAMP);
        $gameWeek->addColumn('event', PostgresAdapter::PHINX_TYPE_INTEGER);
        $gameWeek->addColumn('season_id', PostgresAdapter::PHINX_TYPE_BIG_INTEGER);
        $gameWeek->addForeignKey(['season_id'], 'seasons', ['season_id']);

        $gameWeek->save();
    }
}
