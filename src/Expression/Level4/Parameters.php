<?php
declare(strict_types = 1);

namespace Innmind\UrlTemplate\Expression\Level4;

use Innmind\UrlTemplate\{
    Expression,
    Expression\Name,
    Expression\Expansion,
    Expression\Level1,
    Expression\Level3,
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
final class Parameters implements Expression
{
    private Name $name;
    /** @var ?int<1, max> */
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
            Expansion::parameter,
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

    #[\Override]
    public function expansion(): Expansion
    {
        return Expansion::parameter;
    }

    #[\Override]
    public function expand(Map $values, Map $lists, Map $keys): string
    {
        $name = $this->name->toString();

        return $lists
            ->get($name)
            ->otherwise(static fn() => $keys->get($name))
            ->map($this->expandList(...))
            ->otherwise(
                fn() => $values
                    ->get($name)
                    ->map(function($value) {
                        if ($this->explode) {
                            return $this->explodeList([$value]);
                        }

                        $value = Str::of($this->expression->encode($value));

                        if ($this->mustLimit()) {
                            return \sprintf(
                                ';%s=%s',
                                $this->name->toString(),
                                $value->take($this->limit)->toString(),
                            );
                        }

                        return \sprintf(
                            ';%s=%s',
                            $this->name->toString(),
                            $value->toString(),
                        );
                    }),
            )
            ->match(
                static fn($value) => $value,
                static fn() => '',
            );
    }

    #[\Override]
    public function regex(): Attempt
    {
        if ($this->explode) {
            return Attempt::error(new \LogicException('Explode expression cant be matched'));
        }

        if ($this->mustLimit()) {
            // replace '*' match by the actual limit
            $regex = $this
                ->expression
                ->regex()
                ->map(Str::of(...))
                ->map(
                    fn($regex) => $regex
                        ->dropEnd(2)
                        ->append("{{$this->limit}})")
                        ->toString(),
                );
        } else {
            $regex = $this->expression->regex();
        }

        return $regex->map(fn($regex) => \sprintf(
            '\;%s=%s',
            $this->name->toString(),
            $regex,
        ));
    }

    #[\Override]
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
     * @psalm-assert-if-true int<1, max> $this->limit
     */
    private function mustLimit(): bool
    {
        return \is_int($this->limit);
    }

    /**
     * @param list<string>|list<array{Name, string}> $variablesToExpand
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

                    return Sequence::of($name->toString(), $variableToExpand);
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
            ->prepend(";{$this->name->toString()}=")
            ->toString();
    }

    /**
     * @param list<string>|list<array{Name, string}> $variablesToExpand
     */
    private function explodeList(array $variablesToExpand): string
    {
        $expanded = Sequence::of(...$variablesToExpand)
            ->map(fn($value) => match (true) {
                \is_string($value) => [$this->name, $value],
                default => $value,
            })
            ->map(static fn($pair) => Level3\Parameters::named($pair[0])->expand(
                Map::of([$pair[0]->toString(), $pair[1]]),
                Map::of(),
                Map::of(),
            ));

        return Str::of('')->join($expanded)->toString();
    }
}
