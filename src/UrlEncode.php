<?php
declare(strict_types = 1);

namespace Innmind\UrlTemplate;

use Innmind\Immutable\{
    Str,
    Monoid\Concat,
};

/**
 * @psalm-immutable
 * @internal
 */
enum UrlEncode
{
    case everything;
    case allowReservedCharacters;

    public function encode(string $string): string
    {
        if ($this === self::everything) {
            return \rawurlencode($string);
        }

        return Str::of($string)
            ->split()
            ->map(static fn($char) => $char->toString())
            ->map(self::map(...))
            ->map(Str::of(...))
            ->fold(new Concat)
            ->toString();
    }

    /**
     * @psalm-pure
     */
    private static function map(string $char): string
    {
        $allowed = [
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
        ];

        if (\in_array($char, $allowed, true)) {
            return $char;
        }

        return \rawurlencode($char);
    }
}
