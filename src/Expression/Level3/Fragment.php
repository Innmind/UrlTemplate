<?php
declare(strict_types = 1);

namespace Innmind\UrlTemplate\Expression\Level3;

use Innmind\UrlTemplate\{
    Expression,
    Expression\Name,
    Expression\Level2,
};
use Innmind\Immutable\{
    MapInterface,
    Sequence,
};

final class Fragment implements Expression
{
    private $names;

    public function __construct(Name ...$names)
    {
        $this->names = Sequence::of(...$names);
        $this->expressions = $this->names->map(static function(Name $name): Level2\Reserved {
            return new Level2\Reserved($name);
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
            ->join(',')
            ->prepend('#');
    }

    public function __toString(): string
    {
        return (string) $this
            ->names
            ->join(',')
            ->prepend('{#')
            ->append('}');
    }
}
