<?php
declare(strict_types = 1);

namespace Innmind\UrlTemplate;

use Innmind\Immutable\{
    Str,
    Sequence,
    Monoid\Concat,
};

/**
 * @psalm-immutable
 * @internal
 */
final class UrlEncode
{
    /** @var Sequence<string> */
    private Sequence $safeCharacters;

    public function __construct()
    {
        $this->safeCharacters = Sequence::strings();
    }

    public function __invoke(string $string): string
    {
        if ($this->safeCharacters->empty()) {
            return \rawurlencode($string);
        }

        return Str::of($string)
            ->split()
            ->map(static fn($char) => $char->toString())
            ->map($this->encode(...))
            ->map(Str::of(...))
            ->fold(new Concat)
            ->toString();
    }

    /**
     * @psalm-pure
     */
    public static function allowReservedCharacters(): self
    {
        $self = new self;
        $self->safeCharacters = Sequence::strings(
            ':',
            '/',
            '?',
            '#',
            '[',
            ']',
            '@',
            '!',
            '$',
            '&',
            "'",
            '(',
            ')',
            '*',
            '+',
            ',',
            ';',
            '=',
        );

        return $self;
    }

    private function encode(string $char): string
    {
        return match ($this->safeCharacters->contains($char)) {
            true => $char,
            false => \rawurlencode($char),
        };
    }
}
