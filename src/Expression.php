<?php
declare(strict_types = 1);

namespace Innmind\UrlTemplate;

use Innmind\UrlTemplate\{
    Expression\Name,
    Exception\DomainException,
};
use Innmind\Immutable\{
    Map,
    Str,
};

interface Expression
{
    /**
     * @throws DomainException
     */
    public static function of(Str $string): self;

    /**
     * @param Map<string, scalar|array> $variables
     */
    public function expand(Map $variables): string;
    public function regex(): string;
    public function __toString(): string;
}
