<?php
declare(strict_types = 1);

namespace Innmind\UrlTemplate\Expression;

use Innmind\UrlTemplate\{
    Expression,
    UrlEncode,
    Exception\DomainException,
};
use Innmind\Immutable\{
    MapInterface,
    Str,
};

final class Level1 implements Expression
{
    private $name;
    private $encode;

    public function __construct(Name $name)
    {
        $this->name = $name;
        $this->encode = new UrlEncode;
    }

    /**
     * {@inheritdoc}
     */
    public static function of(Str $string): Expression
    {
        if (!$string->matches('~^\{[a-zA-Z0-9_]+\}$~')) {
            throw new DomainException((string) $string);
        }

        return new self(new Name((string) $string->trim('{}')));
    }

    /**
     * {@inheritdoc}
     */
    public function expand(MapInterface $variables): string
    {
        if (!$variables->contains((string) $this->name)) {
            return '';
        }

        return ($this->encode)(
            (string) $variables->get((string) $this->name)
        );
    }

    public function __toString(): string
    {
        return "{{$this->name}}";
    }
}
