<?php
declare(strict_types = 1);

namespace Innmind\UrlTemplate;

use Innmind\Immutable\Str;
use function Innmind\Immutable\join;

final class UrlEncode
{
    private Str $safeCharacters;

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
            $characters = $string
                ->split()
                ->map(function(Str $character): Str {
                    return Str::of($this($character->toString()));
                })
                ->toSequenceOf(
                    'string',
                    static fn(Str $character): \Generator => yield $character->toString(),
                );

            return join('', $characters)->toString();
        }

        if ($string->empty()) {
            return '';
        }

        if ($this->safeCharacters->contains($string->toString())) {
            return $string->toString();
        }

        return \rawurlencode($string->toString());
    }
}
