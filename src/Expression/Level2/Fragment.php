<?php
declare(strict_types = 1);

namespace Innmind\UrlTemplate\Expression\Level2;

use Innmind\UrlTemplate\{
    Expression,
    Expression\Name,
    UrlEncode,
    Exception\DomainException,
};
use Innmind\Immutable\{
    MapInterface,
    Str,
};

final class Fragment implements Expression
{
    private $name;
    private $encode;
    private $regex;

    public function __construct(Name $name)
    {
        $this->name = $name;
        $this->encode = UrlEncode::allowReservedCharacters();
    }

    /**
     * {@inheritdoc}
     */
    public static function of(Str $string): Expression
    {
        if (!$string->matches('~^\{#[a-zA-Z0-9_]+\}$~')) {
            throw new DomainException((string) $string);
        }

        return new self(new Name((string) $string->trim('{#}')));
    }

    /**
     * {@inheritdoc}
     */
    public function expand(MapInterface $variables): string
    {
        if (!$variables->contains((string) $this->name)) {
            return '';
        }

        return '#'.($this->encode)(
            (string) $variables->get((string) $this->name)
        );
    }

    public function regex(): string
    {
        return $this->regex ?? $this->regex = "\#(?<{$this->name}>[a-zA-Z0-9\%:/\?#\[\]@!\$&'\(\)\*\+,;=\-\.\_\~]*)";
    }

    public function __toString(): string
    {
        return "{#{$this->name}}";
    }
}
