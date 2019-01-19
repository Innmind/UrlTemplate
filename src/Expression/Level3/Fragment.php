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

final class Fragment implements Expression
{
    private $names;
    private $expressions;
    private $regex;
    private $string;

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
        if (!$string->matches('~^\{#[a-zA-Z0-9_]+(,[a-zA-Z0-9_]+)+\}$~')) {
            throw new DomainException((string) $string);
        }

        return new self(
            ...$string
                ->trim('{#}')
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
            ->join(',')
            ->prepend('#');
    }

    public function regex(): string
    {
        return $this->regex ?? $this->regex = (string) $this
            ->expressions
            ->map(static function(Expression $expression): string {
                return $expression->regex();
            })
            ->join(',')
            ->prepend('\#');
    }

    public function __toString(): string
    {
        return $this->string ?? $this->string = (string) $this
            ->names
            ->join(',')
            ->prepend('{#')
            ->append('}');
    }
}
