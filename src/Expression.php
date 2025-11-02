<?php
declare(strict_types = 1);

namespace Innmind\UrlTemplate;

use Innmind\Immutable\Map;

/**
 * @psalm-immutable
 * @internal
 */
interface Expression
{
    public function expansion(): Expression\Expansion;

    /**
     * @param Map<non-empty-string, string> $values
     * @param Map<non-empty-string, list<string>> $lists
     * @param Map<non-empty-string, list<array{string, string}>> $keys
     */
    public function expand(Map $values, Map $lists, Map $keys): string;
    public function regex(): string;
    public function toString(): string;
}
