<?php
declare(strict_types = 1);

namespace Innmind\UrlTemplate;

use Innmind\Immutable\{
    Map,
    Str,
    Maybe,
};

/**
 * @psalm-immutable
 */
interface Expression
{
    /**
     * @psalm-pure
     *
     * @return Maybe<self>
     */
    public static function of(Str $string): Maybe;

    public function expansion(): Expression\Expansion;

    /**
     * @param Map<non-empty-string, string|list<string>|list<array{string, string}>> $variables
     */
    public function expand(Map $variables): string;
    public function regex(): string;
    public function toString(): string;
}
