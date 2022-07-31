<?php
declare(strict_types = 1);

namespace Innmind\UrlTemplate\Expression\Level3;

use Innmind\UrlTemplate\{
    Expression,
    Expression\Name,
    Expression\Level1,
};
use Innmind\Immutable\{
    Map,
    Sequence,
    Str,
    Maybe,
};

/**
 * @psalm-immutable
 */
final class Label implements Expression
{
    /** @var Sequence<Name> */
    private Sequence $names;
    /** @var Sequence<Expression> */
    private Sequence $expressions;

    /**
     * @param Sequence<Name> $names
     */
    private function __construct(Sequence $names)
    {
        $this->names = $names;
        /** @var Sequence<Expression> */
        $this->expressions = $this->names->map(Level1::named(...));
    }

    /**
     * @psalm-pure
     */
    public static function of(Str $string): Maybe
    {
        /** @var Maybe<Expression> */
        return Maybe::just($string)
            ->filter(static fn($string) => $string->matches('~^\{\.[a-zA-Z0-9_]+(,[a-zA-Z0-9_]+)*\}$~'))
            ->map(static fn($string) => $string->trim('{.}')->split(','))
            ->map(
                static fn($names) => $names
                    ->map(static fn($name) => $name->toString())
                    ->map(Name::of(...)),
            )
            ->map(static fn($names) => new self($names));
    }

    public function expand(Map $variables): string
    {
        $expanded = $this->expressions->map(
            static fn($expression) => $expression->expand($variables),
        );

        return Str::of('.')
            ->join($expanded)
            ->prepend('.')
            ->toString();
    }

    public function regex(): string
    {
        return Str::of('.')
            ->join($this->expressions->map(
                static fn($expression) => $expression->regex(),
            ))
            ->replace('\.', '')
            ->prepend('\.')
            ->toString();
    }

    public function toString(): string
    {
        return Str::of(',')
            ->join($this->names->map(
                static fn($element) => $element->toString(),
            ))
            ->prepend('{.')
            ->append('}')
            ->toString();
    }
}
