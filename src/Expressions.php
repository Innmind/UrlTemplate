<?php
declare(strict_types = 1);

namespace Innmind\UrlTemplate;

use Innmind\UrlTemplate\Exception\DomainException;
use Innmind\Immutable\{
    Sequence,
    Str,
    Maybe,
};

/**
 * @psalm-immutable
 */
final class Expressions
{
    /**
     * @psalm-pure
     */
    public static function of(Str $string): Expression
    {
        /** @psalm-suppress MixedArgumentTypeCoercion */
        return self::expressions()
            ->reduce(
                Maybe::nothing(),
                static fn(Maybe $expression, $attempt) => $expression->otherwise(
                    static fn() => $attempt($string),
                ),
            )
            ->match(
                static fn(Expression $expression): Expression => $expression,
                static fn() => throw new DomainException($string->toString()),
            );
    }

    /**
     * @psalm-pure
     *
     * @return Sequence<callable(Str): Maybe<Expression>>
     */
    private static function expressions(): Sequence
    {
        /** @var Sequence<callable(Str): Maybe<Expression>> */
        return Sequence::of(
            Expression\Level4::of(...),
            Expression\Level4\Reserved::of(...),
            Expression\Level4\Fragment::of(...),
            Expression\Level4\Label::of(...),
            Expression\Level4\Path::of(...),
            Expression\Level4\Parameters::of(...),
            Expression\Level4\Query::of(...),
            Expression\Level4\QueryContinuation::of(...),
            Expression\Level3::of(...),
            Expression\Level3\Reserved::of(...),
            Expression\Level3\Fragment::of(...),
            Expression\Level3\Label::of(...),
            Expression\Level3\Path::of(...),
            Expression\Level3\Parameters::of(...),
            Expression\Level3\Query::of(...),
            Expression\Level3\QueryContinuation::of(...),
            Expression\Level4\Composite::of(...),
        );
    }
}
