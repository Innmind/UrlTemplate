<?php
declare(strict_types = 1);

namespace Innmind\UrlTemplate\Expression\Level3;

use Innmind\UrlTemplate\{
    Expression,
    Expression\Name,
    Expression\Expansion,
    Expression\Level2,
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
final class Fragment implements Expression
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
        $this->expressions = $this->names->map(Level2\Reserved::named(...));
    }

    /**
     * @psalm-pure
     */
    public static function of(Str $string): Maybe
    {
        /** @var Maybe<Expression> */
        return Name::many($string, Expansion::fragment)->map(
            static fn($names) => new self($names),
        );
    }

    public function expansion(): Expansion
    {
        return Expansion::fragment;
    }

    public function expand(Map $variables): string
    {
        $expanded = $this->expressions->map(
            static fn($expression) => $expression->expand($variables),
        );

        return Str::of(',')
            ->join($expanded)
            ->prepend('#')
            ->toString();
    }

    public function regex(): string
    {
        return Str::of(',')
            ->join($this->expressions->map(
                static fn($expression) => $expression->regex(),
            ))
            ->prepend('\#')
            ->toString();
    }

    public function toString(): string
    {
        return Str::of(',')
            ->join($this->names->map(
                static fn($element) => $element->toString(),
            ))
            ->prepend('{#')
            ->append('}')
            ->toString();
    }
}
