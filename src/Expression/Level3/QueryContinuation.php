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
final class QueryContinuation implements Expression
{
    private Expression $expression;

    /**
     * @param Sequence<Name> $names
     */
    private function __construct(Sequence $names)
    {
        $this->expression = new NamedValues(Expansion::queryContinuation, $names);
    }

    /**
     * @psalm-pure
     *
     * @return Attempt<self>
     */
    public static function of(Str $string): Attempt
    {
        return Name::many($string, Expansion::queryContinuation)
            ->map(static fn($names) => new self($names))
            ->attempt(static fn() => new \LogicException('Cannot parse level 3'));
    }

    public static function named(Name $name): self
    {
        return new self(Sequence::of($name));
    }

    #[\Override]
    public function expansion(): Expansion
    {
        return Expansion::queryContinuation;
    }

    #[\Override]
    public function expand(Map $variables): string
    {
        return $this->expression->expand($variables);
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
