<?php
declare(strict_types = 1);

namespace Innmind\UrlTemplate\Expression;

use Innmind\UrlTemplate\{
    Expression,
    UrlEncode,
};
use Innmind\Immutable\MapInterface;

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
    public function expand(MapInterface $variables): string
    {
        if (!$variables->contains((string) $this->name)) {
            return '';
        }

        return ($this->encode)($variables->get((string) $this->name));
    }

    public function __toString(): string
    {
        return "{{$this->name}}";
    }
}
