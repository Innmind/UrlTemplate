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
    private function __construct(
        private NamedValues $expression,
    ) {
    }

    /**
     * @psalm-pure
     *
     * @return Attempt<self>
     */
    public static function of(Str $string): Attempt
    {
        return Name::many($string, Expansion::query)
            ->map(NamedValues::query(...))
            ->map(static fn($expression) => new self($expression));
    }

    /**
     * @psalm-pure
     */
    public static function named(Name $name): self
    {
        return new self(NamedValues::query(Sequence::of($name)));
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
    public function regex(): Attempt
    {
        return $this->expression->regex();
    }

    #[\Override]
    public function toString(): string
    {
        return $this->expression->toString();
    }
}
