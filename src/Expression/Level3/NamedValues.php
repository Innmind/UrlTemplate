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
    Attempt,
};

/**
 * @psalm-immutable
 * @internal
 */
final class NamedValues
{
    /**
     * @param Sequence<Name> $names
     */
    private function __construct(
        private Expansion $expansion,
        private Sequence $names,
        private bool $keyOnlyWhenEmpty = false,
    ) {
    }

    /**
     * @psalm-pure
     *
     * @param Sequence<Name> $names
     */
    #[\NoDiscard]
    public static function parameters(Sequence $names): self
    {
        return new self(Expansion::parameter, $names, true);
    }

    /**
     * @psalm-pure
     *
     * @param Sequence<Name> $names
     */
    #[\NoDiscard]
    public static function query(Sequence $names): self
    {
        return new self(Expansion::query, $names, false);
    }

    /**
     * @psalm-pure
     *
     * @param Sequence<Name> $names
     */
    #[\NoDiscard]
    public static function queryContinuation(Sequence $names): self
    {
        return new self(Expansion::queryContinuation, $names, false);
    }

    /**
     * @param Map<non-empty-string, string> $values
     * @param Map<non-empty-string, list<string>> $lists
     * @param Map<non-empty-string, list<array{Name, string}>> $keys
     */
    #[\NoDiscard]
    public function expand(Map $values, Map $lists, Map $keys): string
    {
        $expanded = $this
            ->names
            ->map(Level1::named(...))
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

    /**
     * @return Attempt<string>
     */
    #[\NoDiscard]
    public function regex(): Attempt
    {
        return $this
            ->names
            ->map(fn($name) => Level1::named($name)->regex()->map(
                fn($regex) => \sprintf(
                    '%s=%s%s',
                    $name->toString(),
                    $this->keyOnlyWhenEmpty ? '?' : '',
                    $regex,
                ),
            ))
            ->sink(Sequence::strings())
            ->attempt(static fn($regexes, $regex) => $regex->map($regexes))
            ->map(Str::of($this->expansion->continuation()->regex())->join(...))
            ->map(fn($regex) => $regex->prepend($this->expansion->regex())->toString());
    }

    #[\NoDiscard]
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
