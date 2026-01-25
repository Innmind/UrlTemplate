<?php
declare(strict_types = 1);

namespace Innmind\UrlTemplate;

use Innmind\UrlTemplate\Expression\Name;
use Innmind\Immutable\{
    Map,
    Attempt,
};

/**
 * @psalm-immutable
 * @internal
 */
interface Expression
{
    #[\NoDiscard]
    public function expansion(): Expression\Expansion;

    /**
     * @param Map<non-empty-string, string> $values
     * @param Map<non-empty-string, list<string>> $lists
     * @param Map<non-empty-string, list<array{Name, string}>> $keys
     */
    #[\NoDiscard]
    public function expand(Map $values, Map $lists, Map $keys): string;

    /**
     * @return Attempt<string>
     */
    #[\NoDiscard]
    public function regex(): Attempt;

    #[\NoDiscard]
    public function toString(): string;
}
