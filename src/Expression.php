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
     * @param Map<non-empty-string, string|list<string>|list<array{string, string}>> $variables
     */
    public function expand(Map $variables): string;
    public function regex(): string;
    public function toString(): string;
}
