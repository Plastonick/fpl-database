<?php
declare(strict_types=1);

use Phinx\Db\Adapter\PostgresAdapter;
use Phinx\Migration\AbstractMigration;
use Plastonick\FantasyDatabase\Migration\HelperTrait;

final class CreatePositionData extends AbstractMigration
{
    use HelperTrait;

    public function up(): void
    {
        $positions = $this->createTable('positions', 'position_id');

        $positions->addColumn('name', PostgresAdapter::PHINX_TYPE_STRING);
        $positions->addColumn('abbreviation', PostgresAdapter::PHINX_TYPE_STRING);
        $positions->save();

        /* ------------------ */

        $playerSeasonPositions = $this->createTable('player_season_positions', 'player_season_positions_id');
        $playerSeasonPositions->addColumn('player_id', PostgresAdapter::PHINX_TYPE_BIG_INTEGER, ['null' => false]);
        $playerSeasonPositions->addForeignKey(['player_id'], 'players', ['player_id']);

        $playerSeasonPositions->addColumn('season_id', PostgresAdapter::PHINX_TYPE_BIG_INTEGER, ['null' => false]);
        $playerSeasonPositions->addForeignKey(['season_id'], 'seasons', ['season_id']);

        $playerSeasonPositions->addColumn('position_id', PostgresAdapter::PHINX_TYPE_BIG_INTEGER, ['null' => false]);
        $playerSeasonPositions->addForeignKey(['position_id'], 'positions', ['position_id']);

        $playerSeasonPositions->save();
    }
}
