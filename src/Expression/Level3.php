<?php
declare(strict_types = 1);

namespace Innmind\UrlTemplate\Expression;

use Innmind\UrlTemplate\Expression;
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
final class Level3 implements Expression
{
    /**
     * @param Sequence<Name> $names
     */
    private function __construct(private Sequence $names)
    {
    }

    /**
     * @psalm-pure
     *
     * @return Attempt<self>
     */
    public static function of(Str $string): Attempt
    {
        return Name::many($string, Expansion::simple)
            ->map(static fn($names) => new self($names));
    }

    #[\Override]
    public function expansion(): Expansion
    {
        return Expansion::simple;
    }

    #[\Override]
    public function expand(Map $values, Map $lists, Map $keys): string
    {
        $expanded = $this
            ->names
            ->map(Level1::named(...))
            ->map(static fn($expression) => $expression->expand($values, $lists, $keys));

        return Str::of(',')->join($expanded)->toString();
    }

    #[\Override]
    public function regex(): Attempt
    {
        /** @psalm-suppress InvalidArgument */
        return Attempt::result(
            Str::of(',')
                ->join($this->names->map(
                    static fn(Name $name) => "(?<{$name->toString()}>[a-zA-Z0-9\%\-\.\_\~]*)",
                ))
                ->toString(),
        );
    }

    #[\Override]
    public function toString(): string
    {
        /** @psalm-suppress InvalidArgument */
        return Str::of(',')
            ->join($this->names->map(
                static fn($name) => $name->toString(),
            ))
            ->prepend('{')
            ->append('}')
            ->toString();
    }
}
