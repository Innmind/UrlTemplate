<?php
declare(strict_types = 1);

namespace Innmind\UrlTemplate\Expression;

use Innmind\UrlTemplate\Expression;
use Innmind\Immutable\{
    Map,
    Sequence,
    Str,
    Maybe,
};

/**
 * @psalm-immutable
 */
final class Level3 implements Expression
{
    /** @var Sequence<Name> */
    private Sequence $names;
    /** @var Sequence<Level1> */
    private Sequence $expressions;

    /**
     * @param Sequence<Name> $names
     */
    private function __construct(Sequence $names)
    {
        $this->names = $names;
        $this->expressions = $this->names->map(Level1::named(...));
    }

    /**
     * @psalm-pure
     */
    public static function of(Str $string): Maybe
    {
        /** @var Maybe<Expression> */
        return Name::many($string, Expansion::simple)->map(
            static fn($names) => new self($names),
        );
    }

    public function expand(Map $variables): string
    {
        $expanded = $this->expressions->map(
            static fn($expression) => $expression->expand($variables),
        );

        return Str::of(',')->join($expanded)->toString();
    }

    public function regex(): string
    {
        /** @psalm-suppress InvalidArgument */
        return Str::of(',')
            ->join($this->names->map(
                static fn(Name $name) => "(?<{$name->toString()}>[a-zA-Z0-9\%\-\.\_\~]*)",
            ))
            ->toString();
    }

    public function toString(): string
    {
        return Str::of(',')
            ->join($this->names->map(
                static fn($name) => $name->toString(),
            ))
            ->prepend('{')
            ->append('}')
            ->toString();
    }
}
