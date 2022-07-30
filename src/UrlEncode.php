<?php
declare(strict_types = 1);

namespace Innmind\UrlTemplate;

use Innmind\Immutable\Str;

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

        $string = Str::of($string);

        if ($string->length() > 1) {
            $characters = $string
                ->split()
                ->map(fn($character) => Str::of($this($character->toString())))
                ->map(static fn($character) => $character->toString());

            return Str::of('')->join($characters)->toString();
        }

        if ($string->empty()) {
            return '';
        }

        if ($this->safeCharacters->contains($string->toString())) {
            return $string->toString();
        }

        return \rawurlencode($string->toString());
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
}
