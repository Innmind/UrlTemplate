<?php
declare(strict_types = 1);

namespace Innmind\UrlTemplate\Expression\Level4;

use Innmind\UrlTemplate\{
    Expression,
    Expression\Name,
    Expressions,
    Exception\DomainException,
};
use Innmind\Immutable\{
    MapInterface,
    Sequence,
    Str,
};

final class Composite implements Expression
{
    private $separator;
    private $type;
    private $expressions;
    private $removeLead = false;
    private $regex;
    private $string;

    public function __construct(
        string $separator,
        Expression $level4,
        Expression ...$expressions
    ) {
        $this->separator = $separator;
        $this->type = get_class($level4);
        $this->expressions = Sequence::of($level4, ...$expressions);
    }

    public static function removeLead(
        string $separator,
        Expression $level4,
        Expression ...$expressions
    ): self {
        $self = new self($separator, $level4, ...$expressions);
        $self->removeLead = true;

        return $self;
    }

    public static function of(Str $string): Expression
    {
        if (!$string->matches('~^\{[\+#\./;\?&]?[a-zA-Z0-9_]+(\*|:\d*)?(,[a-zA-Z0-9_]+(\*|:\d*)?)+\}$~')) {
            throw new DomainException((string) $string);
        }

        $pieces = $string
            ->trim('{}')
            ->split(',');

        return $pieces
            ->drop(1)
            ->reduce(
                Expressions::of($pieces->first()->prepend('{')->append('}')),
                static function(Expression $level4, Str $expression): Expression {
                    return $level4->add($expression);
                }
            );
    }

    /**
     * {@inheritdoc}
     */
    public function expand(MapInterface $variables): string
    {
        $values = $this
            ->expressions
            ->map(static function(Expression $expression) use ($variables): string {
                return $expression->expand($variables);
            });

        //potentially remove the lead characters from the expressions except for
        //the first one, needed for the fragment composite

        return (string) $values
            ->take(1)
            ->append(
                $values->drop(1)->map(function(string $value): string {
                    if ($this->removeLead) {
                        return (string) Str::of($value)->substring(1);
                    }

                    return $value;
                })
            )
            ->join($this->separator);
    }

    public function regex(): string
    {
        if (\is_string($this->regex)) {
            return $this->regex;
        }

        $remaining = $this
            ->expressions
            ->drop(1)
            ->map(function(Expression $expression): string {
                if ($this->removeLead) {
                    return (string) Str::of($expression->regex())->substring(2);
                }

                return $expression->regex();
            });

        return $this->regex = (string) $this
            ->expressions
            ->take(1)
            ->map(static function(Expression $expression): string {
                return $expression->regex();
            })
            ->append($remaining)
            ->join(
                $this->separator ? '\\'.$this->separator : ''
            );
    }

    public function __toString(): string
    {
        if (\is_string($this->string)) {
            return $this->string;
        }

        $expressions = $this
            ->expressions
            ->map(static function(Expression $expression): Str {
                return Str::of((string) $expression);
            })
            ->map(static function(Str $expression): Str {
                return $expression->trim('{}');
            });

        //only keep the lead character for the first expression and remove it
        //for the following ones

        return $this->string = (string) $expressions
            ->take(1)
            ->append(
                $expressions
                    ->drop(1)
                    ->map(static function(Str $expression): Str {
                        return $expression->leftTrim('+#/.;?&');
                    })
            )
            ->join(',')
            ->prepend('{')
            ->append('}');
    }
}
