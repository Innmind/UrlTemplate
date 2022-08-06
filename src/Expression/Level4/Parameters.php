<?php
declare(strict_types = 1);

namespace Innmind\UrlTemplate\Expression\Level4;

use Innmind\UrlTemplate\{
    Expression,
    Expression\Name,
    Expression\Expansion,
    Expression\Level1,
    Expression\Level3,
    Expression\Level4,
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
final class Parameters implements Expression
{
    private Name $name;
    /** @var ?positive-int */
    private ?int $limit = null;
    private bool $explode = false;
    private Expression $expression;

    private function __construct(Name $name)
    {
        $this->name = $name;
        $this->expression = Level1::named($name);
    }

    /**
     * @psalm-pure
     */
    public static function of(Str $string): Maybe
    {
        return Parse::of(
            $string,
            static fn(Name $name) => new self($name),
            self::explode(...),
            self::limit(...),
            Expansion::parameter,
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

    public function expansion(): Expansion
    {
        return Expansion::parameter;
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

        $value = Str::of($this->expression->expand($variables));

        if ($this->mustLimit()) {
            return ";{$this->name->toString()}={$value->take($this->limit)->toString()}";
        }

        return ";{$this->name->toString()}={$value->toString()}";
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
            '\;%s=%s',
            $this->name->toString(),
            $regex,
        );
    }

    public function toString(): string
    {
        if ($this->mustLimit()) {
            return "{;{$this->name->toString()}:{$this->limit}}";
        }

        if ($this->explode) {
            return "{;{$this->name->toString()}*}";
        }

        return "{;{$this->name->toString()}}";
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
                $variables = ($variables)($this->name->toString(), $variableToExpand);

                return $this->expression->expand($variables);
            },
        );

        return Str::of(',')
            ->join($expanded)
            ->prepend(";{$this->name->toString()}=")
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
                $name = $this->name;

                if (\is_array($variableToExpand)) {
                    [$name, $value] = $variableToExpand;
                    $name = Name::of($name);
                    $variableToExpand = $value;
                }

                $variables = ($variables)($name->toString(), $variableToExpand);

                return Level3\Parameters::named($name)->expand($variables);
            },
        );

        return Str::of('')->join($expanded)->toString();
    }
}
