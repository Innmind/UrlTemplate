<?php
declare(strict_types = 1);

namespace Innmind\UrlTemplate;

use Innmind\Immutable\{
    Sequence,
    Str,
    Attempt,
};

/**
 * @psalm-immutable
 * @internal
 */
final class Expressions
{
    /**
     * @psalm-pure
     *
     * @return Attempt<Expression>
     */
    #[\NoDiscard]
    public static function of(Str $string): Attempt
    {
        return self::expressions()
            ->lookup()
            ->first()
            ->attempt(
                new \LogicException('Failed to parse template'),
                static fn($expression) => $expression($string),
            );
    }

    /**
     * @psalm-pure
     *
     * @return Sequence<callable(Str): Attempt<Expression>>
     */
    private static function expressions(): Sequence
    {
        /** @var Sequence<callable(Str): Attempt<Expression>> */
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
