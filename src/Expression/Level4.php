<?php
declare(strict_types = 1);

namespace Innmind\UrlTemplate\Expression;

use Innmind\UrlTemplate\{
    Expression,
    Exception\ExplodeExpressionCantBeMatched,
};
use Innmind\Immutable\{
    Map,
    Str,
    Sequence,
    Attempt,
};

/**
 * @psalm-immutable
 * @internal
 */
final class Level4 implements Expression
{
    private Name $name;
    private Level1|Level2\Reserved $expression;
    /** @var ?int<1, max> */
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
     *
     * @return Attempt<self>
     */
    public static function of(Str $string): Attempt
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
     * @param int<1, max> $limit
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

    #[\Override]
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
     * @param pure-callable(Name): Level2\Reserved $expression
     */
    public function withExpression(callable $expression): self
    {
        $self = clone $this;
        $self->expression = $expression($self->name);

        return $self;
    }

    #[\Override]
    public function expand(Map $values, Map $lists, Map $keys): string
    {
        // todo break up
        /**
         * @psalm-suppress InvalidArgument
         * @var Map<non-empty-string, string|list<string>|list<array{string, string}>>
         */
        $variables = $values
            ->merge($lists)
            ->merge($keys);
        $variable = $variables->get($this->name->toString())->match(
            static fn($variable) => $variable,
            static fn() => null,
        );

        if (\is_null($variable)) {
            return '';
        }

        if (\is_array($variable)) {
            return $this->expandList($variable);
        }

        if ($this->explode) {
            return $this->explodeList([$variable]);
        }

        if ($this->mustLimit()) {
            $value = Str::of($variable)->take($this->limit);
            $value = $this->expression->encode($value->toString());
        } else {
            $value = $this->expression->encode($variable);
        }

        return "{$this->expansion->toString()}$value";
    }

    #[\Override]
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

    #[\Override]
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
     * @psalm-assert-if-true int<1, max> $this->limit
     */
    private function mustLimit(): bool
    {
        return \is_int($this->limit);
    }

    /**
     * @param list<string>|list<array{string, string}> $variablesToExpand
     */
    private function expandList(array $variablesToExpand): string
    {
        if ($this->explode) {
            return $this->explodeList($variablesToExpand);
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

        // here we use the level1 expression to transform the variable to
        // be expanded to its string representation
        $expanded = $flattenedVariables->map(
            $this->expression->encode(...),
        );

        return $this->separator()
            ->join($expanded)
            ->prepend($this->expansion->toString())
            ->toString();
    }

    /**
     * @param list<string>|list<array{string, string}> $variablesToExpand
     */
    private function explodeList(array $variablesToExpand): string
    {
        $expanded = Sequence::of(...$variablesToExpand)
            ->map(fn($value) => match (true) {
                \is_string($value) => $this->expression->encode($value),
                default => [
                    $value[0],
                    $this->expression->encode($value[1]),
                ],
            })
            ->map(static fn($value) => match (true) {
                \is_string($value) => $value,
                default => \sprintf(
                    '%s=%s',
                    Name::of($value[0])->toString(), // todo move verification earlier on
                    $value[1],
                ),
            });

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
