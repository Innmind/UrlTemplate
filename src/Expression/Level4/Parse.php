<?php
declare(strict_types = 1);

namespace Innmind\UrlTemplate\Expression\Level4;

use Innmind\UrlTemplate\{
    Expression,
    Expression\Name,
    Exception\DomainException,
    Exception\ExpressionLimitCantBeNegative,
};
use Innmind\Immutable\Str;

final class Parse
{
    /**
     * @psalm-pure
     * @template T of Expression
     *
     * @param pure-callable(Name): T $standard
     * @param pure-callable(Name): T $explode
     * @param pure-callable(Name, int<0, max>): T $limit
     * @param non-empty-string|null $lead
     *
     * @return T
     */
    public static function of(
        Str $string,
        callable $standard,
        callable $explode,
        callable $limit,
        string $lead = null,
    ): Expression {
        $drop = match ($lead) {
            null => 1,
            default => 2,
        };
        $lead = match ($lead) {
            null => '',
            default => "\\$lead",
        };

        if ($string->matches("~^\{{$lead}[a-zA-Z0-9_]+\}\$~")) {
            return $standard(Name::of($string->drop($drop)->dropEnd(1)->toString()));
        }

        if ($string->matches("~^\{{$lead}[a-zA-Z0-9_]+\*\}\$~")) {
            return $explode(Name::of($string->drop($drop)->dropEnd(2)->toString()));
        }

        if ($string->matches("~^\{{$lead}[a-zA-Z0-9_]+:\d+\}\$~")) {
            $string = $string->drop($drop)->dropEnd(1);
            [$name, $int] = $string->split(':')->toList();
            $int = (int) $int->toString();

            if ($int < 0) {
                throw new ExpressionLimitCantBeNegative($int);
            }

            /** @psalm-suppress ArgumentTypeCoercion */
            return $limit(Name::of($name->toString()), $int);
        }

        throw new DomainException($string->toString());
    }
}
