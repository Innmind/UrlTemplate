<?php
declare(strict_types = 1);

namespace Innmind\UrlTemplate\Expression\Level3;

use Innmind\UrlTemplate\{
    Expression,
    Expression\Name,
    Expression\Level1,
};
use Innmind\Immutable\{
    MapInterface,
    Sequence,
};

final class Label implements Expression
{
    private $names;

    public function __construct(Name ...$names)
    {
        $this->names = Sequence::of(...$names);
        $this->expressions = $this->names->map(static function(Name $name): Level1 {
            return new Level1($name);
        });
    }

    /**
     * {@inheritdoc}
     */
    public function expand(MapInterface $variables): string
    {
        return (string) $this
            ->expressions
            ->map(static function(Expression $expression) use ($variables): string {
                return $expression->expand($variables);
            })
            ->join('.')
            ->prepend('.');
    }

    public function __toString(): string
    {
        return (string) $this
            ->names
            ->join(',')
            ->prepend('{.')
            ->append('}');
    }
}
