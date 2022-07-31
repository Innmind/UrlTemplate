<?php
declare(strict_types = 1);

namespace Innmind\UrlTemplate\Expression\Level4;

use Innmind\UrlTemplate\{
    Expression,
    Expression\Name,
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
     * @param ?non-empty-string $lead
     *
     * @return Maybe<Expression>
     */
    public static function of(
        Str $string,
        callable $standard,
        callable $explode,
        callable $limit,
        string $lead = null,
    ): Maybe {
        return Name::one($string, $lead)
            ->map($standard)
            ->otherwise(static fn() => self::explode($string, $explode, $lead))
            ->otherwise(static fn() => self::limit($string, $limit, $lead));
    }

    /**
     * @psalm-pure
     *
     * @param pure-callable(Name): Expression $explode
     * @param ?non-empty-string $lead
     *
     * @return Maybe<Expression>
     */
    private static function explode(
        Str $string,
        callable $explode,
        string $lead = null,
    ): Maybe {
        return Name::explode($string, $lead)->map($explode);
    }

    /**
     * @psalm-pure
     *
     * @param pure-callable(Name, positive-int): Expression $limit
     * @param ?non-empty-string $lead
     *
     * @return Maybe<Expression>
     */
    private static function limit(
        Str $string,
        callable $limit,
        string $lead = null,
    ): Maybe {
        return Name::limit($string, $lead)->map(
            static fn($tuple) => $limit($tuple[0], $tuple[1]),
        );
    }
}
