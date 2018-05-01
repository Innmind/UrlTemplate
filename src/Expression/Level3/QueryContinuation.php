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
    Map,
    Sequence,
    StreamInterface,
    Stream,
};

final class QueryContinuation implements Expression
{
    private $names;

    public function __construct(Name ...$names)
    {
        $this->names = Sequence::of(...$names);
        $this->expressions = $this->names->reduce(
            new Map('string', Expression::class),
            static function(MapInterface $expressions, Name $name): MapInterface {
                return $expressions->put(
                    (string) $name,
                    new Level1($name)
                );
            });
    }

    /**
     * {@inheritdoc}
     */
    public function expand(MapInterface $variables): string
    {
        return (string) $this
            ->expressions
            ->reduce(
                Stream::of('string'),
                static function(StreamInterface $values, string $name, Expression $expression) use ($variables): StreamInterface {
                    $value = $expression->expand($variables);

                    return $values->add("$name=$value");
                }
            )
            ->join('&')
            ->prepend('&');
    }

    public function __toString(): string
    {
        return (string) $this
            ->names
            ->join(',')
            ->prepend('{&')
            ->append('}');
    }
}
