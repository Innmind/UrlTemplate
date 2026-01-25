<?php
declare(strict_types = 1);

namespace Innmind\UrlTemplate\Expression;

use Innmind\UrlTemplate\Expression;
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
    /**
     * @param ?int<1, max> $limit
     */
    private function __construct(
        private Level1|Level2\Reserved $expression,
        private ?int $limit,
        private bool $explode,
        private Expansion $expansion,
    ) {
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
            self::named(...),
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
        return new self(
            Level1::named($name),
            $limit,
            false,
            Expansion::simple,
        );
    }

    /**
     * @psalm-pure
     */
    public static function explode(Name $name): self
    {
        return new self(
            Level1::named($name),
            null,
            true,
            Expansion::simple,
        );
    }

    /**
     * @psalm-pure
     */
    public static function named(Name $name): self
    {
        return new self(
            Level1::named($name),
            null,
            false,
            Expansion::simple,
        );
    }

    public function name(): Name
    {
        return $this->expression->name();
    }

    #[\Override]
    public function expansion(): Expansion
    {
        return Expansion::simple;
    }

    public function withExpansion(Expansion $expansion): self
    {
        return new self(
            $this->expression,
            $this->limit,
            $this->explode,
            $expansion,
        );
    }

    /**
     * Not ideal technic but didn't find a better to reduce duplicated code
     * @internal
     *
     * @param pure-callable(Name): Level2\Reserved $expression
     */
    public function withExpression(callable $expression): self
    {
        return new self(
            $expression($this->name()),
            $this->limit,
            $this->explode,
            $this->expansion,
        );
    }

    #[\Override]
    public function expand(Map $values, Map $lists, Map $keys): string
    {
        $name = $this->name()->toString();

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

                        if ($this->mustLimit()) {
                            $value = Str::of($value)->take($this->limit)->toString();
                        }

                        $value = Str::of($this->expression->encode($value));

                        return "{$this->expansion->toString()}$value";
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
            '%s%s',
            $this->expansion->regex(),
            $regex,
        ));
    }

    #[\Override]
    public function toString(): string
    {
        if ($this->mustLimit()) {
            return "{{$this->expansion->toString()}{$this->name()->toString()}:{$this->limit}}";
        }

        if ($this->explode) {
            return "{{$this->expansion->toString()}{$this->name()->toString()}*}";
        }

        return "{{$this->expansion->toString()}{$this->name()->toString()}}";
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

        return $this->separator()
            ->join($expanded)
            ->prepend($this->expansion->toString())
            ->toString();
    }

    /**
     * @param list<string>|list<array{Name, string}> $variablesToExpand
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
                    $value[0]->toString(),
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
