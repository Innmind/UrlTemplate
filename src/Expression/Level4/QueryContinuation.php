<?php
declare(strict_types = 1);

namespace Innmind\UrlTemplate\Expression\Level4;

use Innmind\UrlTemplate\{
    Expression,
    Expression\Name,
    Expression\Level1,
    Expression\Level3,
    Expression\Level4,
    Exception\DomainException,
    Exception\ExplodeExpressionCantBeMatched,
    Exception\ExpressionLimitCantBeNegative,
};
use Innmind\Immutable\{
    Map,
    Str,
    Sequence,
};

/**
 * @psalm-immutable
 */
final class QueryContinuation implements Expression
{
    private Name $name;
    private ?int $limit = null;
    private bool $explode = false;
    private Expression $expression;

    public function __construct(Name $name)
    {
        $this->name = $name;
        $this->expression = new Level1($name);
    }

    /**
     * @psalm-pure
     */
    public static function of(Str $string): Expression
    {
        if ($string->matches('~^\{\&[a-zA-Z0-9_]+\}$~')) {
            return new self(new Name($string->trim('{&}')->toString()));
        }

        if ($string->matches('~^\{\&[a-zA-Z0-9_]+\*\}$~')) {
            return self::explode(new Name($string->trim('{&*}')->toString()));
        }

        if ($string->matches('~^\{\&[a-zA-Z0-9_]+:\d+\}$~')) {
            $string = $string->trim('{&}');
            [$name, $limit] = $string->split(':')->toList();

            return self::limit(
                new Name($name->toString()),
                (int) $limit->toString(),
            );
        }

        throw new DomainException($string->toString());
    }

    /**
     * @psalm-pure
     */
    public static function limit(Name $name, int $limit): self
    {
        if ($limit < 0) {
            throw new ExpressionLimitCantBeNegative($limit);
        }

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

    public function add(Str $pattern): Composite
    {
        return new Composite(
            '',
            $this,
            self::of($pattern->prepend('{&')->append('}')),
        );
    }

    /**
     * @param Map<string, scalar|array> $variables
     */
    public function expand(Map $variables): string
    {
        /** @var scalar|array{0:string, 1:scalar}|null */
        $variable = $variables->get($this->name->toString())->match(
            static fn($variable) => $variable,
            static fn() => null,
        );

        if (\is_null($variable)) {
            return '';
        }

        if (\is_array($variable)) {
            return $this->expandList($variables, ...$variable);
        }

        if ($this->explode) {
            return $this->expandList($variables, $variable);
        }

        $value = Str::of($this->expression->expand($variables));

        if ($this->mustLimit()) {
            return "&{$this->name->toString()}={$value->substring(0, $this->limit)->toString()}";
        }

        return "&{$this->name->toString()}={$value->toString()}";
    }

    public function regex(): string
    {
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

        return \sprintf(
            '\&%s=%s',
            $this->name->toString(),
            $regex,
        );
    }

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

    private function mustLimit(): bool
    {
        return \is_int($this->limit);
    }

    /**
     * @no-named-arguments
     * @param Map<string, scalar|array> $variables
     * @param array<scalar|array{0:string, 1:scalar}> $variablesToExpand
     */
    private function expandList(Map $variables, ...$variablesToExpand): string
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
        $expanded = $flattenedVariables->map(function($variableToExpand) use ($variables): string {
            // here we use the level1 expression to transform the variable to
            // be expanded to its string representation
            $variables = ($variables)($this->name->toString(), $variableToExpand);

            return $this->expression->expand($variables);
        });

        return Str::of(',')
            ->join($expanded)
            ->prepend("&{$this->name->toString()}=")
            ->toString();
    }

    /**
     * @param Map<string, scalar|array> $variables
     * @param array<scalar|array{0:string, 1:scalar}> $variablesToExpand
     */
    private function explodeList(Map $variables, array $variablesToExpand): string
    {
        /** @psalm-suppress NamedArgumentNotAllowed */
        $expanded = Sequence::of(...$variablesToExpand)->map(function($variableToExpand) use ($variables): string {
            $name = $this->name;

            if (\is_array($variableToExpand)) {
                [$name, $value] = $variableToExpand;
                $name = new Name($name);
                $variableToExpand = $value;
            }

            $variables = ($variables)($name->toString(), $variableToExpand);

            return (new Level3\QueryContinuation($name))->expand($variables);
        });

        return Str::of('')->join($expanded)->toString();
    }
}
