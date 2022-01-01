<?php

namespace Plastonick\FantasyDatabase\Hydration;

trait ExtractionTrait
{
    private function extractData(string $type, ?string $raw): int|string|null
    {
        if ($raw === null) {
            return null;
        }

        return match ($type) {
            'integer' => (int) $raw,
            'bool' => in_array(strtolower($raw), ['true', '1', 'y']) ? 1 : 0,
            'datetime' => $raw ?: null,
            default => $raw
        };
    }
}
