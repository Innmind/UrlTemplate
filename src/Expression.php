<?php
declare(strict_types = 1);

namespace Innmind\UrlTemplate;

use Innmind\UrlTemplate\{
    Expression\Name,
    Exception\DomainException,
};
use Innmind\Immutable\{
    MapInterface,
    Str,
};

interface Expression
{
    /**
     * @throws DomainException
     */
    public static function of(Str $string): self;

    /**
     * @param MapInterface<string, variable> $variables
     */
    public function expand(MapInterface $variables): string;
    public function __toString(): string;
}
