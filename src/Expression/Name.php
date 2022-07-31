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
     * @param ?non-empty-string $lead
     *
     * @return Maybe<self>
     */
    public static function one(
        Str $value,
        string $lead = null,
    ): Maybe {
        return self::find($value, $lead)
            ->map(static fn($value) => $value->toString())
            ->map(static fn($value) => new self($value));
    }

    /**
     * @psalm-pure
     *
     * @param ?non-empty-string $lead
     *
     * @return Maybe<self>
     */
    public static function explode(
        Str $value,
        string $lead = null,
    ): Maybe {
        return self::find($value, $lead, '\\*')
            ->map(static fn($value) => $value->toString())
            ->map(static fn($value) => new self($value));
    }

    /**
     * @psalm-pure
     *
     * @param ?non-empty-string $lead
     *
     * @return Maybe<array{self, positive-int}>
     */
    public static function limit(
        Str $value,
        string $lead = null,
    ): Maybe {
        return self::find($value, $lead, ':\d+')
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
     * @param ?non-empty-string $lead
     *
     * @return Maybe<Sequence<self>>
     */
    public static function many(
        Str $value,
        string $lead = null,
    ): Maybe {
        $drop = match ($lead) {
            null => 1,
            default => 2,
        };
        $lead = match ($lead) {
            null => '',
            default => "\\$lead",
        };

        return Maybe::just($value)
            ->filter(static fn($value) => $value->matches("~^\{{$lead}[a-zA-Z0-9_]+(,[a-zA-Z0-9_]+)*\}\$~"))
            ->map(static fn($value) => $value->drop($drop)->dropEnd(1)->split(','))
            ->map(
                static fn($values) => $values
                    ->map(static fn($value) => $value->toString())
                    ->map(static fn($value) => new self($value)),
            );
    }

    public function toString(): string
    {
        return $this->value;
    }

    /**
     * @psalm-pure
     *
     * @param ?non-empty-string $lead
     * @param ?non-empty-string $extra
     *
     * @return Maybe<Str>
     */
    private static function find(
        Str $value,
        string $lead = null,
        string $extra = null,
    ): Maybe {
        $drop = match ($lead) {
            null => 1,
            default => 2,
        };
        $dropEnd = match ($extra) {
            '\\*' => 2,
            default => 1,
        };
        $lead = match ($lead) {
            null => '',
            default => "\\$lead",
        };

        return Maybe::just($value)
            ->filter(static fn($value) => $value->matches("~^\{{$lead}[a-zA-Z0-9_]+{$extra}\}\$~"))
            ->map(static fn($value) => $value->drop($drop)->dropEnd($dropEnd));
    }
}
