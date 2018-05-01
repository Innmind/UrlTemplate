<?php
declare(strict_types = 1);

namespace Innmind\UrlTemplate\Expression\Level3;

use Innmind\UrlTemplate\{
    Expression,
    Expression\Name,
};
use Innmind\Immutable\MapInterface;

final class Parameters implements Expression
{
    private $expression;

    public function __construct(Name ...$names)
    {
        $this->expression = NamedValues::keyOnlyWhenEmpty(';', ';', ...$names);
    }

    /**
     * {@inheritdoc}
     */
    public function expand(MapInterface $variables): string
    {
        return $this->expression->expand($variables);
    }

    public function __toString(): string
    {
        return (string) $this->expression;
    }
}
