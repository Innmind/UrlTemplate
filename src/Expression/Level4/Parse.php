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
     * @param non-empty-string|null $lead
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
        $drop = match ($lead) {
            null => 1,
            default => 2,
        };
        $lead = match ($lead) {
            null => '',
            default => "\\$lead",
        };

        return Maybe::just($string)
            ->filter(static fn($string) => $string->matches("~^\{{$lead}[a-zA-Z0-9_]+\}\$~"))
            ->map(static fn($string) => $string->drop($drop)->dropEnd(1)->toString())
            ->map(Name::of(...))
            ->map($standard)
            ->otherwise(static fn() => self::explode($string, $explode, $lead, $drop))
            ->otherwise(static fn() => self::limit($string, $limit, $lead, $drop));
    }

    /**
     * @psalm-pure
     *
     * @param pure-callable(Name): Expression $explode
     * @param positive-int $drop
     *
     * @return Maybe<Expression>
     */
    private static function explode(
        Str $string,
        callable $explode,
        string $lead,
        int $drop,
    ): Maybe {
        return Maybe::just($string)
            ->filter(static fn($string) => $string->matches("~^\{{$lead}[a-zA-Z0-9_]+\*\}\$~"))
            ->map(static fn($string) => $string->drop($drop)->dropEnd(2)->toString())
            ->map(Name::of(...))
            ->map($explode);
    }

    /**
     * @psalm-pure
     *
     * @param pure-callable(Name, positive-int): Expression $limit
     * @param positive-int $drop
     *
     * @return Maybe<Expression>
     */
    private static function limit(
        Str $string,
        callable $limit,
        string $lead,
        int $drop,
    ): Maybe {
        /** @psalm-suppress ArgumentTypeCoercion For the positive-int */
        return Maybe::just($string)
            ->filter(static fn($string) => $string->matches("~^\{{$lead}[a-zA-Z0-9_]+:\d+\}\$~"))
            ->map(static fn($string) => $string->drop($drop)->dropEnd(1)->split(':'))
            ->map(static fn($pieces) => $pieces->map(static fn($piece) => $piece->toString()))
            ->flatMap(
                static fn($pieces) => $pieces
                    ->first()
                    ->map(Name::of(...))
                    ->flatMap(
                        static fn($name) => $pieces
                            ->last()
                            ->filter(\is_numeric(...))
                            ->map(static fn($limit) => (int) $limit)
                            ->filter(static fn(int $limit) => $limit > 0)
                            ->map(static fn($int) => $limit($name, $int)),
                    ),
            );
    }
}
