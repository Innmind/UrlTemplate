<?php
declare(strict_types = 1);

namespace Innmind\UrlTemplate;

use Innmind\Immutable\{
    Str,
    Monoid\Concat,
};

/**
 * @psalm-immutable
 */
final class UrlEncode
{
    private Str $safeCharacters;

    public function __construct()
    {
        $this->safeCharacters = Str::of('');
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
        $self->safeCharacters = Str::of(':/?#[]@!$&\'()*+,;=');

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
