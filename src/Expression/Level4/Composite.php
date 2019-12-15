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
    Map,
    Sequence,
    Str,
};
use function Innmind\Immutable\join;

final class Composite implements Expression
{
    private string $separator;
    private string $type;
    private Sequence $expressions;
    private bool $removeLead = false;
    private ?string $regex = null;
    private ?string $string = null;

    public function __construct(
        string $separator,
        Expression $level4,
        Expression ...$expressions
    ) {
        $this->separator = $separator;
        $this->type = \get_class($level4);
        $this->expressions = Sequence::mixed($level4, ...$expressions);
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
            throw new DomainException($string->toString());
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
    public function expand(Map $variables): string
    {
        $values = $this
            ->expressions
            ->map(static function(Expression $expression) use ($variables): string {
                return $expression->expand($variables);
            });

        //potentially remove the lead characters from the expressions except for
        //the first one, needed for the fragment composite

        $values = $values
            ->take(1)
            ->append(
                $values->drop(1)->map(function(string $value): string {
                    if ($this->removeLead) {
                        return Str::of($value)->substring(1)->toString();
                    }

                    return $value;
                })
            )
            ->toSequenceOf(
                'string',
                static fn($element): \Generator => yield (string) $element,
            );

        return join($this->separator, $values)->toString();
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
                    return Str::of($expression->regex())->substring(2)->toString();
                }

                return $expression->regex();
            });

        return $this->regex = join(
            $this->separator ? '\\'.$this->separator : '',
            $this
                ->expressions
                ->take(1)
                ->map(static function(Expression $expression): string {
                    return $expression->regex();
                })
                ->append($remaining)
                ->toSequenceOf(
                    'string',
                    static fn($element): \Generator => yield (string) $element,
                )
        )->toString();
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

        $expressions = $expressions
            ->take(1)
            ->append(
                $expressions
                    ->drop(1)
                    ->map(static function(Str $expression): Str {
                        return $expression->leftTrim('+#/.;?&');
                    })
            )
            ->toSequenceOf(
                'string',
                static fn($element): \Generator => yield $element->toString(),
            );

        return $this->string = join(',', $expressions)
            ->prepend('{')
            ->append('}')
            ->toString();
    }
}
