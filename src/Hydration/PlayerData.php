<?php

namespace Plastonick\FantasyDatabase\Hydration;

final class PlayerData
{
    public function __construct(
        public readonly string $firstName,
        public readonly string $secondName,
        public readonly string $webName,
        public readonly int $elementCode,
        public readonly int $elementType,
        public readonly int $teamId
    ) {
    }
}
