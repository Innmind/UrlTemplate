<?php
declare(strict_types = 1);

namespace Innmind\UrlTemplate\Expression\Level3;

use Innmind\UrlTemplate\Expression\{
    Name,
    Expansion,
    Level1,
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
final class NamedValues
{
    private Expansion $expansion;
    /** @var Sequence<Name> */
    private Sequence $names;
    /** @var Sequence<Level1> */
    private Sequence $expressions;
    private bool $keyOnlyWhenEmpty = false;

    /**
     * @param Sequence<Name> $names
     */
    public function __construct(Expansion $expansion, Sequence $names)
    {
        $this->expansion = $expansion;
        $this->names = $names;
        $this->expressions = $names->map(Level1::named(...));
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

    /**
     * @param Map<non-empty-string, string> $values
     * @param Map<non-empty-string, list<string>> $lists
     * @param Map<non-empty-string, list<array{string, string}>> $keys
     */
    public function expand(Map $values, Map $lists, Map $keys): string
    {
        $expanded = $this
            ->expressions
            ->map(static fn($expression) => [
                $expression->name()->toString(),
                Str::of($expression->expand($values, $lists, $keys)),
            ])
            ->map(fn($pair) => match ([$pair[1]->empty(), $this->keyOnlyWhenEmpty]) {
                [true, true] => $pair[0],
                default => \sprintf(
                    '%s=%s',
                    $pair[0],
                    $pair[1]->toString(),
                ),
            });

        return Str::of($this->expansion->continuation()->toString())
            ->join($expanded)
            ->prepend($this->expansion->toString())
            ->toString();
    }

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
