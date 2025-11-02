<?php
declare(strict_types = 1);

namespace Innmind\UrlTemplate\Expression\Level3;

use Innmind\UrlTemplate\{
    Expression,
    Expression\Name,
    Expression\Expansion,
    Expression\Level1,
};
use Innmind\Immutable\{
    Map,
    Sequence,
    Str,
};

/**
 * @psalm-immutable
 * @internal
 */
final class NamedValues implements Expression
{
    private Expansion $expansion;
    /** @var Sequence<Name> */
    private Sequence $names;
    /** @var Map<string, Level1> */
    private Map $expressions;
    private bool $keyOnlyWhenEmpty = false;

    /**
     * @param Sequence<Name> $names
     */
    public function __construct(Expansion $expansion, Sequence $names)
    {
        $this->expansion = $expansion;
        $this->names = $names;
        /** @var Map<string, Level1> */
        $this->expressions = Map::of(
            ...$this
                ->names
                ->map(static fn($name) => [
                    $name->toString(),
                    Level1::named($name),
                ])
                ->toList(),
        );
    }

    /**
     * @psalm-pure
     *
     * @param Sequence<Name> $names
     */
    public static function keyOnlyWhenEmpty(Expansion $expansion, Sequence $names): self
    {
        $self = new self($expansion, $names);
        $self->keyOnlyWhenEmpty = true;

        return $self;
    }

    #[\Override]
    public function expansion(): Expansion
    {
        return $this->expansion;
    }

    #[\Override]
    public function expand(Map $values, Map $lists, Map $keys): string
    {
        $expanded = $this
            ->expressions
            ->map(static fn($_, $expression) => $expression->expand($values, $lists, $keys))
            ->map(static fn($_, $expression) => Str::of($expression))
            ->toSequence()
            ->map(fn($pair) => match ([$pair->value()->empty(), $this->keyOnlyWhenEmpty]) {
                [true, true] => $pair->key(),
                default => \sprintf(
                    '%s=%s',
                    $pair->key(),
                    $pair->value()->toString(),
                ),
            });

        return Str::of($this->expansion->continuation()->toString())
            ->join($expanded)
            ->prepend($this->expansion->toString())
            ->toString();
    }

    #[\Override]
    public function regex(): string
    {
        return Str::of($this->expansion->continuation()->regex())
            ->join($this->names->map(
                fn($name) => \sprintf(
                    '%s=%s%s',
                    $name->toString(),
                    $this->keyOnlyWhenEmpty ? '?' : '',
                    Level1::named($name)->regex(),
                ),
            ))
            ->prepend($this->expansion->regex())
            ->toString();
    }

    #[\Override]
    public function toString(): string
    {
        /** @psalm-suppress InvalidArgument */
        return Str::of(',')
            ->join($this->names->map(
                static fn($element) => $element->toString(),
            ))
            ->prepend('{'.$this->expansion->toString())
            ->append('}')
            ->toString();
    }
}
