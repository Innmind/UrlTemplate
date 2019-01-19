<?php
declare(strict_types = 1);

namespace Innmind\UrlTemplate\Expression;

use Innmind\UrlTemplate\{
    Expression,
    Exception\DomainException,
};
use Innmind\Immutable\{
    MapInterface,
    SequenceInterface,
    Sequence,
    Str,
};

final class Level3 implements Expression
{
    private $names;
    private $regex;

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
    public static function of(Str $string): Expression
    {
        if (!$string->matches('~^\{[a-zA-Z0-9_]+(,[a-zA-Z0-9_]+)+\}$~')) {
            throw new DomainException((string) $string);
        }

        return new self(
            ...$string
                ->trim('{}')
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

    public function regex(): string
    {
        return $this->regex ?? $this->regex = (string) $this
            ->names
            ->map(static function(Name $name): string {
                return "(?<{$name}>[a-zA-Z0-9\%\-\.\_\~]*)";
            })
            ->join(',');
    }

    public function __toString(): string
    {
        return (string) $this
            ->names
            ->join(',')
            ->prepend('{')
            ->append('}');
    }
}
