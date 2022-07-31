<?php
declare(strict_types = 1);

namespace Innmind\UrlTemplate\Expression;

use Innmind\UrlTemplate\Exception\DomainException;
use Innmind\Immutable\{
    Str,
    Maybe,
    Sequence,
};

/**
 * @psalm-immutable
 */
final class Name
{
    private string $value;

    private function __construct(string $value)
    {
        $this->value = $value;
    }

    /**
     * @psalm-pure
     */
    public static function of(string $value): self
    {
        if (!Str::of($value)->matches('~[a-zA-Z0-9_]+~')) {
            throw new DomainException($value);
        }

        return new self($value);
    }

    /**
     * @psalm-pure
     *
     * @return Maybe<self>
     */
    public static function one(
        Str $value,
        Expansion $expansion,
    ): Maybe {
        return Maybe::just($value)
            ->filter($expansion->matches(...))
            ->map($expansion->clean(...))
            ->map(static fn($value) => $value->toString())
            ->map(static fn($value) => new self($value));
    }

    /**
     * @psalm-pure
     *
     * @return Maybe<self>
     */
    public static function explode(
        Str $value,
        Expansion $expansion,
    ): Maybe {
        return Maybe::just($value)
            ->filter($expansion->matchesExplode(...))
            ->map($expansion->cleanExplode(...))
            ->map(static fn($value) => $value->toString())
            ->map(static fn($value) => new self($value));
    }

    /**
     * @psalm-pure
     *
     * @return Maybe<array{self, positive-int}>
     */
    public static function limit(
        Str $value,
        Expansion $expansion,
    ): Maybe {
        return Maybe::just($value)
            ->filter($expansion->matchesLimit(...))
            ->map($expansion->clean(...))
            ->map(static fn($value) => $value->split(':'))
            ->map(static fn($pieces) => $pieces->map(static fn($piece) => $piece->toString()))
            ->flatMap(
                static fn($pieces) => $pieces
                    ->first()
                    ->map(static fn($value) => new self($value))
                    ->flatMap(
                        static fn($name) => $pieces
                            ->last()
                            ->filter(\is_numeric(...))
                            ->map(static fn($limit) => (int) $limit)
                            ->filter(static fn(int $limit) => $limit > 0)
                            ->map(static fn($int) => [$name, $int]),
                    ),
            );
    }

    /**
     * @psalm-pure
     *
     * @return Maybe<Sequence<self>>
     */
    public static function many(
        Str $value,
        Expansion $expansion,
    ): Maybe {
        return Maybe::just($value)
            ->filter($expansion->matchesMany(...))
            ->map($expansion->clean(...))
            ->map(
                static fn($value) => $value
                    ->split(',')
                    ->map(static fn($value) => $value->toString())
                    ->map(static fn($value) => new self($value)),
            );
    }

    public function toString(): string
    {
        return $this->value;
    }
}
