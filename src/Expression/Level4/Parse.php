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
    Attempt,
};

/**
 * @internal
 */
final class Parse
{
    /**
     * @psalm-pure
     * @internal
     * @template T of Expression
     *
     * @param pure-callable(Name): T $standard
     * @param pure-callable(Name): T $explode
     * @param pure-callable(Name, positive-int): T $limit
     *
     * @return Attempt<T>
     */
    public static function of(
        Str $string,
        callable $standard,
        callable $explode,
        callable $limit,
        Expansion $expansion,
    ): Attempt {
        return Name::one($string, $expansion)
            ->map($standard)
            ->otherwise(static fn() => self::explode($string, $explode, $expansion))
            ->otherwise(static fn() => self::limit($string, $limit, $expansion))
            ->attempt(static fn() => new \LogicException('Cannot parse level 4'));
    }

    /**
     * @psalm-pure
     * @template T of Expression
     *
     * @param pure-callable(Name): T $explode
     *
     * @return Maybe<T>
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
     * @template T of Expression
     *
     * @param pure-callable(Name, positive-int): T $limit
     *
     * @return Maybe<T>
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
