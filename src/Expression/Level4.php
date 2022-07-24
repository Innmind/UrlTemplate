<?php
declare(strict_types = 1);

namespace Innmind\UrlTemplate\Expression;

use Innmind\UrlTemplate\{
    Expression,
    Expression\Level4\Composite,
    Exception\DomainException,
    Exception\ExplodeExpressionCantBeMatched,
    Exception\ExpressionLimitCantBeNegative,
};
use Innmind\Immutable\{
    Map,
    Str,
    Sequence,
};
use function Innmind\Immutable\{
    join,
    unwrap,
};

final class Level4 implements Expression
{
    private Name $name;
    private Expression $expression;
    private ?int $limit = null;
    private bool $explode = false;
    private string $lead = '';
    private string $separator = ',';
    private ?string $regex = null;
    private ?string $string = null;

    public function __construct(Name $name)
    {
        $this->name = $name;
        $this->expression = new Level1($name);
    }

    public static function of(Str $string): Expression
    {
        if ($string->matches('~^\{[a-zA-Z0-9_]+\}$~')) {
            return new self(new Name($string->trim('{}')->toString()));
        }

        if ($string->matches('~^\{[a-zA-Z0-9_]+\*\}$~')) {
            return self::explode(new Name($string->trim('{*}')->toString()));
        }

        if ($string->matches('~^\{[a-zA-Z0-9_]+:\d+\}$~')) {
            $string = $string->trim('{}');
            [$name, $limit] = unwrap($string->split(':'));

            return self::limit(
                new Name($name->toString()),
                (int) $limit->toString(),
            );
        }

        throw new DomainException($string->toString());
    }

    public static function limit(Name $name, int $limit): self
    {
        if ($limit < 0) {
            throw new ExpressionLimitCantBeNegative($limit);
        }

        $self = new self($name);
        $self->limit = $limit;

        return $self;
    }

    public static function explode(Name $name): self
    {
        $self = new self($name);
        $self->explode = true;

        return $self;
    }

    public function add(Str $pattern): Composite
    {
        return new Composite(
            ',',
            $this,
            self::of($pattern->prepend('{')->append('}')),
        );
    }

    public function withLead(string $lead): self
    {
        $self = clone $this;
        $self->lead = $lead;

        return $self;
    }

    public function withSeparator(string $separator): self
    {
        $self = clone $this;
        $self->separator = $separator;

        return $self;
    }

    /**
     * Not ideal technic but didn't find a better to reduce duplicated code
     * @internal
     *
     * @param class-string<Expression> $expression
     */
    public function withExpression(string $expression): self
    {
        if (!\is_a($expression, Expression::class, true)) {
            throw new DomainException($expression);
        }

        $self = clone $this;
        $self->expression = new $expression($self->name);

        return $self;
    }

    public function expand(Map $variables): string
    {
        if (!$variables->contains($this->name->toString())) {
            return '';
        }

        /** @var scalar|array{0:string, 1:scalar} */
        $variable = $variables->get($this->name->toString());

        if (\is_array($variable)) {
            return $this->expandList($variables, ...$variable);
        }

        if ($this->explode) {
            return $this->explodeList($variables, [$variable]);
        }

        if ($this->mustLimit()) {
            $value = Str::of((string) $variable)->substring(0, $this->limit);
            $value = $this->expression->expand(
                ($variables)($this->name->toString(), $value->toString()),
            );
        } else {
            $value = $this->expression->expand($variables);
        }

        return "{$this->lead}$value";
    }

    public function regex(): string
    {
        if (\is_string($this->regex)) {
            return $this->regex;
        }

        if ($this->explode) {
            throw new ExplodeExpressionCantBeMatched;
        }

        if ($this->mustLimit()) {
            // replace '*' match by the actual limit
            $regex = Str::of($this->expression->regex())
                ->substring(0, -2)
                ->append("{{$this->limit}})")
                ->toString();
        } else {
            $regex = $this->expression->regex();
        }

        return $this->regex = \sprintf(
            '%s%s',
            $this->lead ? '\\'.$this->lead : '',
            $regex,
        );
    }

    public function toString(): string
    {
        if (\is_string($this->string)) {
            return $this->string;
        }

        if ($this->mustLimit()) {
            return $this->string = "{{$this->lead}{$this->name->toString()}:{$this->limit}}";
        }

        if ($this->explode) {
            return $this->string = "{{$this->lead}{$this->name->toString()}*}";
        }

        return $this->string = "{{$this->lead}{$this->name->toString()}}";
    }

    private function mustLimit(): bool
    {
        return \is_int($this->limit);
    }

    /**
     * @param Map<string, scalar|array> $variables
     * @param array<scalar|array{0:scalar, 1:scalar}> $variablesToExpand
     */
    private function expandList(Map $variables, ...$variablesToExpand): string
    {
        if ($this->explode) {
            return $this->explodeList($variables, $variablesToExpand);
        }

        /** @var Sequence<scalar> */
        $flattenedVariables = Sequence::of('scalar|array', ...$variablesToExpand)->reduce(
            Sequence::of('scalar'),
            static function(Sequence $values, $variableToExpand): Sequence {
                if (\is_array($variableToExpand)) {
                    [$name, $variableToExpand] = $variableToExpand;

                    return ($values)($name)($variableToExpand);
                }

                return ($values)($variableToExpand);
            },
        );

        $expanded = $flattenedVariables
            ->map(function($variableToExpand) use ($variables): string {
                // here we use the level1 expression to transform the variable to
                // be expanded to its string representation
                return $this->expression->expand(
                    ($variables)($this->name->toString(), $variableToExpand),
                );
            })
            ->toSequenceOf('string');

        return join($this->separator, $expanded)
            ->prepend($this->lead)
            ->toString();
    }

    /**
     * @param Map<string, scalar|array> $variables
     * @param array<scalar|array{0:scalar, 1:scalar}> $variablesToExpand
     */
    private function explodeList(Map $variables, array $variablesToExpand): string
    {
        $expanded = Sequence::of('scalar|array', ...$variablesToExpand)
            ->map(function($variableToExpand) use ($variables): string {
                if (\is_array($variableToExpand)) {
                    [$name, $value] = $variableToExpand;
                    $variableToExpand = $value;
                }

                /** @psalm-suppress MixedArgument */
                $variables = ($variables)($this->name->toString(), $variableToExpand);

                $value = $this->expression->expand($variables);

                if (isset($name)) {
                    /** @psalm-suppress MixedArgument */
                    $name = new Name($name);
                    $value = \sprintf(
                        '%s=%s',
                        $name->toString(),
                        $value,
                    );
                }

                return $value;
            })
            ->toSequenceOf('string');

        return join($this->separator, $expanded)
            ->prepend($this->lead)
            ->toString();
    }
}
