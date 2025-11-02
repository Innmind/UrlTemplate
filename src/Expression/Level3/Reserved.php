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
    Attempt,
};

/**
 * @psalm-immutable
 * @internal
 */
final class Reserved implements Expression
{
    /** @var Sequence<Name> */
    private Sequence $names;
    /** @var Sequence<Level2\Reserved> */
    private Sequence $expressions;

    /**
     * @param Sequence<Name> $names
     */
    private function __construct(Sequence $names)
    {
        $this->names = $names;
        /** @var Sequence<Level2\Reserved> */
        $this->expressions = $this->names->map(Level2\Reserved::named(...));
    }

    /**
     * @psalm-pure
     *
     * @return Attempt<self>
     */
    public static function of(Str $string): Attempt
    {
        return Name::many($string, Expansion::reserved)
            ->map(static fn($names) => new self($names));
    }

    #[\Override]
    public function expansion(): Expansion
    {
        return Expansion::reserved;
    }

    #[\Override]
    public function expand(Map $values, Map $lists, Map $keys): string
    {
        $expanded = $this->expressions->map(
            static fn($expression) => $expression->expand($values, $lists, $keys),
        );

        return Str::of(',')->join($expanded)->toString();
    }

    #[\Override]
    public function regex(): string
    {
        return Str::of(',')
            ->join($this->expressions->map(
                static fn($expression) => $expression->regex(),
            ))
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
            ->prepend('{+')
            ->append('}')
            ->toString();
    }
}
