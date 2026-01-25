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
    private function __construct(
        private Name $name,
        private UrlEncode $encode,
    ) {
    }

    /**
     * @psalm-pure
     *
     * @return Attempt<self>
     */
    public static function of(Str $string): Attempt
    {
        return Name::one($string, Expansion::reserved)
            ->map(self::named(...));
    }

    /**
     * @psalm-pure
     */
    public static function named(Name $name): self
    {
        return new self($name, UrlEncode::allowReservedCharacters);
    }

    public function name(): Name
    {
        return $this->name;
    }

    #[\Override]
    public function expansion(): Expansion
    {
        return Expansion::reserved;
    }

    #[\Override]
    public function expand(Map $values, Map $lists, Map $keys): string
    {
        return $values
            ->get($this->name->toString())
            ->match(
                $this->encode->encode(...),
                static fn() => '',
            );
    }

    public function encode(string $string): string
    {
        return $this->encode->encode($string);
    }

    #[\Override]
    public function regex(): Attempt
    {
        return Attempt::result("(?<{$this->name->toString()}>[a-zA-Z0-9\%:/\?#\[\]@!\$&'\(\)\*\+,;=\-\.\_\~]*)");
    }

    #[\Override]
    public function toString(): string
    {
        return"{+{$this->name->toString()}}";
    }
}
