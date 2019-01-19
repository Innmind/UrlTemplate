<?php
declare(strict_types = 1);

namespace Innmind\UrlTemplate;

use Innmind\Immutable\Str;

final class UrlEncode
{
    private $safeCharacters;

    public function __construct()
    {
        $this->safeCharacters = Str::of('');
    }

    public static function allowReservedCharacters(): self
    {
        $self = new self;
        $self->safeCharacters = Str::of(':/?#[]@!$&\'()*+,;=');

        return $self;
    }

    public function __invoke(string $string): string
    {
        if ($this->safeCharacters->empty()) {
            return \rawurlencode($string);
        }

        $string = Str::of($string);

        if ($string->length() > 1) {
            return (string) $string
                ->split()
                ->map(function(Str $character): Str {
                    return Str::of($this((string) $character));
                })
                ->join('');
        }

        if ($string->empty()) {
            return '';
        }

        if ($this->safeCharacters->contains((string) $string)) {
            return (string) $string;
        }

        return \rawurlencode((string) $string);
    }
}
