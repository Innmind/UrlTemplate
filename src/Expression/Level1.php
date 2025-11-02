<?php
declare(strict_types = 1);

namespace Innmind\UrlTemplate\Expression;

use Innmind\UrlTemplate\{
    Expression,
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
     *
     * @return Attempt<self>
     */
    public static function of(Str $string): Attempt
    {
        return Name::one($string, Expansion::simple)
            ->map(static fn($name) => new self($name));
    }

    /**
     * @psalm-pure
     */
    public static function named(Name $name): self
    {
        return new self($name);
    }

    public function name(): Name
    {
        return $this->name;
    }

    #[\Override]
    public function expansion(): Expansion
    {
        return Expansion::simple;
    }

    #[\Override]
    public function expand(Map $values, Map $lists, Map $keys): string
    {
        return $values
            ->get($this->name->toString())
            ->match(
                $this->encode,
                static fn() => '',
            );
    }

    public function encode(string $string): string
    {
        return ($this->encode)($string);
    }

    #[\Override]
    public function regex(): string
    {
        return "(?<{$this->name->toString()}>[a-zA-Z0-9\%\-\.\_\~]*)";
    }

    #[\Override]
    public function toString(): string
    {
        return "{{$this->name->toString()}}";
    }
}
