<?php
declare(strict_types = 1);

namespace Innmind\UrlTemplate\Expression\Level3;

use Innmind\UrlTemplate\{
    Expression,
    Expression\Name,
    Expression\Expansion,
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
final class Query implements Expression
{
    private NamedValues $expression;

    /**
     * @param Sequence<Name> $names
     */
    private function __construct(Sequence $names)
    {
        $this->expression = new NamedValues(Expansion::query, $names);
    }

    /**
     * @psalm-pure
     *
     * @return Attempt<self>
     */
    public static function of(Str $string): Attempt
    {
        return Name::many($string, Expansion::query)
            ->map(static fn($names) => new self($names));
    }

    /**
     * @psalm-pure
     */
    public static function named(Name $name): self
    {
        return new self(Sequence::of($name));
    }

    #[\Override]
    public function expansion(): Expansion
    {
        return Expansion::query;
    }

    #[\Override]
    public function expand(Map $values, Map $lists, Map $keys): string
    {
        return $this->expression->expand($values, $lists, $keys);
    }

    #[\Override]
    public function regex(): string
    {
        return $this->expression->regex();
    }

    #[\Override]
    public function toString(): string
    {
        return $this->expression->toString();
    }
}
