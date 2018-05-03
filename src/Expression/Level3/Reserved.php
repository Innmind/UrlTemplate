<?php
declare(strict_types = 1);

namespace Innmind\UrlTemplate\Expression\Level3;

use Innmind\UrlTemplate\{
    Expression,
    Expression\Name,
    Expression\Level2,
    Exception\DomainException,
};
use Innmind\Immutable\{
    MapInterface,
    SequenceInterface,
    Sequence,
    Str,
};

final class Reserved implements Expression
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
    public static function of(Str $string): Expression
    {
        if (!$string->matches('~^\{\+[a-zA-Z0-9_]+(,[a-zA-Z0-9_]+)+\}$~')) {
            throw new DomainException((string) $string);
        }

        return new self(
            ...$string
                ->trim('{+}')
                ->split(',')
                ->reduce(
                    new Sequence,
                    static function(SequenceInterface $names, Str $name): SequenceInterface {
                        return $names->add(new Name((string) $name));
                    }
                )
        );
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
            ->join(',');
    }

    public function __toString(): string
    {
        return (string) $this
            ->names
            ->join(',')
            ->prepend('{+')
            ->append('}');
    }
}
