<?php
declare(strict_types = 1);

namespace Innmind\UrlTemplate\Expression;

use Innmind\UrlTemplate\{
    Expression,
    UrlEncode,
    Exception\OnlyScalarCanBeExpandedForExpression,
};
use Innmind\Immutable\{
    Map,
    Str,
    Maybe,
};

/**
 * @psalm-immutable
 */
final class Level1 implements Expression
{
    private Name $name;
    private UrlEncode $encode;

    private function __construct(Name $name)
    {
        $this->name = $name;
        $this->encode = new UrlEncode;
    }

    /**
     * @psalm-pure
     */
    public static function of(Str $string): Maybe
    {
        /** @var Maybe<Expression> */
        return Name::one($string, Expansion::simple)->map(
            static fn($name) => new self($name),
        );
    }

    /**
     * @psalm-pure
     */
    public static function named(Name $name): self
    {
        return new self($name);
    }

    public function expansion(): Expansion
    {
        return Expansion::simple;
    }

    public function expand(Map $variables): string
    {
        /** @psalm-suppress InvalidArgument Because of the filter */
        return $variables
            ->get($this->name->toString())
            ->filter(\is_string(...))
            ->match(
                fn(string $variable) => ($this->encode)($variable),
                static fn() => '',
            );
    }

    public function regex(): string
    {
        return "(?<{$this->name->toString()}>[a-zA-Z0-9\%\-\.\_\~]*)";
    }

    public function toString(): string
    {
        return "{{$this->name->toString()}}";
    }
}
