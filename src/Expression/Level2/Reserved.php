<?php
declare(strict_types = 1);

namespace Innmind\UrlTemplate\Expression\Level2;

use Innmind\UrlTemplate\{
    Expression,
    Expression\Name,
    Expression\Expansion,
    UrlEncode,
};
use Innmind\Immutable\{
    Map,
    Str,
    Attempt,
};

/**
 * @psalm-immutable
 * @internal
 */
final class Reserved implements Expression
{
    private Name $name;
    private UrlEncode $encode;

    private function __construct(Name $name)
    {
        $this->name = $name;
        $this->encode = UrlEncode::allowReservedCharacters();
    }

    /**
     * @psalm-pure
     *
     * @return Attempt<self>
     */
    public static function of(Str $string): Attempt
    {
        return Name::one($string, Expansion::reserved)
            ->map(static fn($name) => new self($name));
    }

    /**
     * @psalm-pure
     */
    public static function named(Name $name): self
    {
        return new self($name);
    }

    #[\Override]
    public function expansion(): Expansion
    {
        return Expansion::reserved;
    }

    #[\Override]
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

    #[\Override]
    public function regex(): string
    {
        return "(?<{$this->name->toString()}>[a-zA-Z0-9\%:/\?#\[\]@!\$&'\(\)\*\+,;=\-\.\_\~]*)";
    }

    #[\Override]
    public function toString(): string
    {
        return"{+{$this->name->toString()}}";
    }
}
