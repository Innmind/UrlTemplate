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
    Maybe,
};

/**
 * @psalm-immutable
 */
final class Parameters implements Expression
{
    private Expression $expression;

    /**
     * @param Sequence<Name> $names
     */
    private function __construct(Sequence $names)
    {
        $this->expression = NamedValues::keyOnlyWhenEmpty(';', ';', $names);
    }

    /**
     * @psalm-pure
     */
    public static function of(Str $string): Maybe
    {
        /** @var Maybe<Expression> */
        return Name::many($string, Expansion::parameter)->map(
            static fn($names) => new self($names),
        );
    }

    /**
     * @psalm-pure
     */
    public static function named(Name $name): self
    {
        return new self(Sequence::of($name));
    }

    public function expand(Map $variables): string
    {
        return $this->expression->expand($variables);
    }

    public function regex(): string
    {
        return $this->expression->regex();
    }

    public function toString(): string
    {
        return $this->expression->toString();
    }
}
