<?php
declare(strict_types=1);

use Phinx\Db\Adapter\PostgresAdapter;
use Phinx\Migration\AbstractMigration;
use Plastonick\FantasyDatabase\Hydration\FixturesHydration;
use Plastonick\FantasyDatabase\Migration\HelperTrait;

final class CreateFixtures extends AbstractMigration
{
    use HelperTrait;

    public function up(): void
    {
        $fixtures = $this->createTable('fixtures', 'fixture_id');
        foreach (FixturesHydration::HEADERS as $header => $type) {
            $fixtures = $this->addColumn($fixtures, $header, $type);
        }

        // link to the season
        $fixtures->addColumn('season_id', PostgresAdapter::PHINX_TYPE_BIG_INTEGER, ['null' => false]);
        $fixtures->addForeignKey(['season_id'], 'seasons', ['season_id']);

        // link to home and away teams
        $fixtures->addColumn('away_team_id', PostgresAdapter::PHINX_TYPE_BIG_INTEGER, ['null' => false]);
        $fixtures->addForeignKey(['away_team_id'], 'teams', ['team_id']);
        $fixtures->addColumn('home_team_id', PostgresAdapter::PHINX_TYPE_BIG_INTEGER, ['null' => false]);
        $fixtures->addForeignKey(['home_team_id'], 'teams', ['team_id']);

        $fixtures->addIndex(['season_id', 'event']);
        $fixtures->addIndex(['kickoff_time']);

        $fixtures->save();
    }
}
