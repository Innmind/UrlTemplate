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
final class Fragment implements Expression
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
        return Name::one($string, Expansion::fragment)
            ->map(static fn($name) => new self($name));
    }

    #[\Override]
    public function expansion(): Expansion
    {
        return Expansion::fragment;
    }

    #[\Override]
    public function expand(Map $values, Map $lists, Map $keys): string
    {
        return $values
            ->get($this->name->toString())
            ->map($this->encode)
            ->match(
                static fn(string $variable) => '#'.$variable,
                static fn() => '',
            );
    }

    #[\Override]
    public function regex(): Attempt
    {
        return Attempt::result("\#(?<{$this->name->toString()}>[a-zA-Z0-9\%:/\?#\[\]@!\$&'\(\)\*\+,;=\-\.\_\~]*)");
    }

    #[\Override]
    public function toString(): string
    {
        return "{#{$this->name->toString()}}";
    }
}
