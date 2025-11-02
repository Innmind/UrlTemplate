<?php
declare(strict_types = 1);

namespace Innmind\UrlTemplate\Expression\Level4;

use Innmind\UrlTemplate\{
    Expression,
    Expression\Name,
    Expression\Expansion,
    Expression\Level1,
    Expression\Level3,
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
final class QueryContinuation implements Expression
{
    private Name $name;
    /** @var ?positive-int */
    private ?int $limit = null;
    private bool $explode = false;
    private Level1 $expression;

    private function __construct(Name $name)
    {
        $this->name = $name;
        $this->expression = Level1::named($name);
    }

    /**
     * @psalm-pure
     *
     * @return Attempt<self>
     */
    public static function of(Str $string): Attempt
    {
        return Parse::of(
            $string,
            static fn(Name $name) => new self($name),
            self::explode(...),
            self::limit(...),
            Expansion::queryContinuation,
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

    #[\Override]
    public function expansion(): Expansion
    {
        return Expansion::queryContinuation;
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

        $value = Str::of($this->expression->encode($variable));

        if ($this->mustLimit()) {
            return "&{$this->name->toString()}={$value->take($this->limit)->toString()}";
        }

        return "&{$this->name->toString()}={$value->toString()}";
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
            '\&%s=%s',
            $this->name->toString(),
            $regex,
        );
    }

    #[\Override]
    public function toString(): string
    {
        if ($this->mustLimit()) {
            return "{&{$this->name->toString()}:{$this->limit}}";
        }

        if ($this->explode) {
            return "{&{$this->name->toString()}*}";
        }

        return "{&{$this->name->toString()}}";
    }

    /**
     * @psalm-assert-if-true positive-int $this->limit
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

        return Str::of(',')
            ->join($expanded)
            ->prepend("&{$this->name->toString()}=")
            ->toString();
    }

    /**
     * @param list<string>|list<array{string, string}> $variablesToExpand
     */
    private function explodeList(array $variablesToExpand): string
    {
        $expanded = Sequence::of(...$variablesToExpand)
            ->map(fn($value) => match (true) {
                \is_string($value) => [$this->name, $value],
                default => [
                    Name::of($value[0]), // todo move wrapping earlier on
                    $value[1],
                ],
            })
            ->map(static fn($pair) => Level3\QueryContinuation::named($pair[0])->expand( // todo simplify
                Map::of([$pair[0]->toString(), $pair[1]]),
                Map::of(),
                Map::of(),
            ));

        return Str::of('')->join($expanded)->toString();
    }
}
