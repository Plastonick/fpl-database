<?php
declare(strict_types=1);

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
        $fixtures->save();
    }
}
