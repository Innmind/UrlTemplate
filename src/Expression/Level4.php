<?php
declare(strict_types = 1);

namespace Innmind\UrlTemplate\Expression;

use Innmind\UrlTemplate\{
    Expression,
    Expression\Level4\Composite,
    Exception\DomainException,
    Exception\ExplodeExpressionCantBeMatched,
};
use Innmind\Immutable\{
    Map,
    Str,
    Sequence,
    Maybe,
};

/**
 * @psalm-immutable
 */
final class Level4 implements Expression
{
    private Name $name;
    private Expression $expression;
    /** @var ?positive-int */
    private ?int $limit = null;
    private bool $explode = false;
    private Expansion $expansion;

    private function __construct(Name $name)
    {
        $this->name = $name;
        $this->expression = Level1::named($name);
        $this->expansion = Expansion::simple;
    }

    /**
     * @psalm-pure
     */
    public static function of(Str $string): Maybe
    {
        return Level4\Parse::of(
            $string,
            static fn(Name $name) => new self($name),
            self::explode(...),
            self::limit(...),
            Expansion::simple,
        );
    }

    /**
     * @psalm-pure
     *
     * @param positive-int $limit
     */
    public static function limit(Name $name, int $limit): self
    {
        $self = new self($name);
        $self->limit = $limit;

        return $self;
    }

    /**
     * @psalm-pure
     */
    public static function explode(Name $name): self
    {
        $self = new self($name);
        $self->explode = true;

        return $self;
    }

    /**
     * @psalm-pure
     */
    public static function named(Name $name): self
    {
        return new self($name);
    }

    public function expansion(): Expansion
    {
        return Expansion::simple;
    }

    public function withExpansion(Expansion $expansion): self
    {
        $self = clone $this;
        $self->expansion = $expansion;

        return $self;
    }

    /**
     * Not ideal technic but didn't find a better to reduce duplicated code
     * @internal
     *
     * @param pure-callable(Name): Expression $expression
     */
    public function withExpression(callable $expression): self
    {
        $self = clone $this;
        $self->expression = $expression($self->name);

        return $self;
    }

    public function expand(Map $variables): string
    {
        $variable = $variables->get($this->name->toString())->match(
            static fn($variable) => $variable,
            static fn() => null,
        );

        if (\is_null($variable)) {
            return '';
        }

        if (\is_array($variable)) {
            return $this->expandList($variables, $variable);
        }

        if ($this->explode) {
            return $this->explodeList($variables, [$variable]);
        }

        if ($this->mustLimit()) {
            $value = Str::of($variable)->take($this->limit);
            $value = $this->expression->expand(
                ($variables)($this->name->toString(), $value->toString()),
            );
        } else {
            $value = $this->expression->expand($variables);
        }

        return "{$this->expansion->toString()}$value";
    }

    public function regex(): string
    {
        if ($this->explode) {
            throw new ExplodeExpressionCantBeMatched;
        }

        if ($this->mustLimit()) {
            // replace '*' match by the actual limit
            $regex = Str::of($this->expression->regex())
                ->dropEnd(2)
                ->append("{{$this->limit}})")
                ->toString();
        } else {
            $regex = $this->expression->regex();
        }

        return \sprintf(
            '%s%s',
            $this->expansion->regex(),
            $regex,
        );
    }

    public function toString(): string
    {
        if ($this->mustLimit()) {
            return "{{$this->expansion->toString()}{$this->name->toString()}:{$this->limit}}";
        }

        if ($this->explode) {
            return "{{$this->expansion->toString()}{$this->name->toString()}*}";
        }

        return "{{$this->expansion->toString()}{$this->name->toString()}}";
    }

    /**
     * @psalm-assert-if-true positive-int $this->limit
     */
    private function mustLimit(): bool
    {
        return \is_int($this->limit);
    }

    /**
     * @param Map<non-empty-string, string|list<string>|list<array{string, string}>> $variables
     * @param list<string>|list<array{string, string}> $variablesToExpand
     */
    private function expandList(Map $variables, array $variablesToExpand): string
    {
        if ($this->explode) {
            return $this->explodeList($variables, $variablesToExpand);
        }

        $flattenedVariables = Sequence::of(...$variablesToExpand)->flatMap(
            static function($variableToExpand): Sequence {
                if (\is_array($variableToExpand)) {
                    [$name, $variableToExpand] = $variableToExpand;

                    return Sequence::of($name, $variableToExpand);
                }

                return Sequence::of($variableToExpand);
            },
        );

        $expanded = $flattenedVariables->map(
            function($variableToExpand) use ($variables): string {
                // here we use the level1 expression to transform the variable to
                // be expanded to its string representation
                return $this->expression->expand(
                    ($variables)($this->name->toString(), $variableToExpand),
                );
            },
        );

        return $this->separator()
            ->join($expanded)
            ->prepend($this->expansion->toString())
            ->toString();
    }

    /**
     * @param Map<non-empty-string, string|list<string>|list<array{string, string}>> $variables
     * @param list<string>|list<array{string, string}> $variablesToExpand
     */
    private function explodeList(Map $variables, array $variablesToExpand): string
    {
        $expanded = Sequence::of(...$variablesToExpand)->map(
            function($variableToExpand) use ($variables): string {
                if (\is_array($variableToExpand)) {
                    [$name, $value] = $variableToExpand;
                    $variableToExpand = $value;
                }

                $variables = ($variables)($this->name->toString(), $variableToExpand);

                $value = $this->expression->expand($variables);

                if (isset($name)) {
                    /** @psalm-suppress MixedArgument */
                    $name = Name::of($name);
                    $value = \sprintf(
                        '%s=%s',
                        $name->toString(),
                        $value,
                    );
                }

                return $value;
            },
        );

        return $this->separator()
            ->join($expanded)
            ->prepend($this->expansion->toString())
            ->toString();
    }

    private function separator(): Str
    {
        if (!$this->explode) {
            return Str::of(',');
        }

        return Str::of($this->expansion->explodeSeparator());
    }
}
