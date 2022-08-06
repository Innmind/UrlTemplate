<?php
declare(strict_types = 1);

namespace Innmind\UrlTemplate\Expression\Level4;

use Innmind\UrlTemplate\{
    Expression,
    Expression\Name,
    Expression\Expansion,
};
use Innmind\Immutable\{
    Str,
    Maybe,
};

final class Parse
{
    /**
     * @psalm-pure
     *
     * @param pure-callable(Name): Expression $standard
     * @param pure-callable(Name): Expression $explode
     * @param pure-callable(Name, positive-int): Expression $limit
     *
     * @return Maybe<Expression>
     */
    public static function of(
        Str $string,
        callable $standard,
        callable $explode,
        callable $limit,
        Expansion $expansion,
    ): Maybe {
        return Name::one($string, $expansion)
            ->map($standard)
            ->otherwise(static fn() => self::explode($string, $explode, $expansion))
            ->otherwise(static fn() => self::limit($string, $limit, $expansion));
    }

    /**
     * @psalm-pure
     *
     * @param pure-callable(Name): Expression $explode
     *
     * @return Maybe<Expression>
     */
    private static function explode(
        Str $string,
        callable $explode,
        Expansion $expansion,
    ): Maybe {
        return Name::explode($string, $expansion)->map($explode);
    }

    /**
     * @psalm-pure
     *
     * @param pure-callable(Name, positive-int): Expression $limit
     *
     * @return Maybe<Expression>
     */
    private static function limit(
        Str $string,
        callable $limit,
        Expansion $expansion,
    ): Maybe {
        return Name::limit($string, $expansion)->map(
            static fn($tuple) => $limit($tuple[0], $tuple[1]),
        );
    }
}
