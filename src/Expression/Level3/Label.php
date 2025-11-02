<?php
declare(strict_types = 1);

namespace Innmind\UrlTemplate\Expression\Level3;

use Innmind\UrlTemplate\{
    Expression,
    Expression\Name,
    Expression\Expansion,
    Expression\Level1,
};
use Innmind\Immutable\{
    Map,
    Sequence,
    Str,
    Attempt,
};

/**
 * @psalm-immutable
 * @internal
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
     *
     * @return Attempt<self>
     */
    public static function of(Str $string): Attempt
    {
        return Name::many($string, Expansion::label)
            ->map(static fn($names) => new self($names));
    }

    #[\Override]
    public function expansion(): Expansion
    {
        return Expansion::label;
    }

    #[\Override]
    public function expand(Map $values, Map $lists, Map $keys): string
    {
        $expanded = $this->expressions->map(
            static fn($expression) => $expression->expand($values, $lists, $keys),
        );

        return Str::of('.')
            ->join($expanded)
            ->prepend('.')
            ->toString();
    }

    #[\Override]
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

    #[\Override]
    public function toString(): string
    {
        /** @psalm-suppress InvalidArgument */
        return Str::of(',')
            ->join($this->names->map(
                static fn($element) => $element->toString(),
            ))
            ->prepend('{.')
            ->append('}')
            ->toString();
    }
}
