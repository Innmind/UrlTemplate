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
    public function expansion(): Expression\Expansion;

    /**
     * @param Map<non-empty-string, string> $values
     * @param Map<non-empty-string, list<string>> $lists
     * @param Map<non-empty-string, list<array{Name, string}>> $keys
     */
    public function expand(Map $values, Map $lists, Map $keys): string;

    /**
     * @return Attempt<string>
     */
    public function regex(): Attempt;
    public function toString(): string;
}
