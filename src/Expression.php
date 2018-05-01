<?php
declare(strict_types = 1);

namespace Innmind\UrlTemplate;

use Innmind\UrlTemplate\Expression\Name;
use Innmind\Immutable\MapInterface;

interface Expression
{
    public function name(): Name;

    /**
     * @param MapInterface<string, variable> $variables
     */
    public function expand(MapInterface $variables): string;
    public function __toString(): string;
}
