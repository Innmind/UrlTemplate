<?php
declare(strict_types = 1);

namespace Innmind\UrlTemplate\Expression;

use Innmind\Immutable\Str;

/**
 * @psalm-immutable
 */
enum Expansion
{
    case simple;
    case reserved;
    case fragment;
    case label;
    case path;
    case parameter;
    case query;
    case queryContinuation;

    public function clean(Str $value): Str
    {
        $drop = match ($this) {
            self::simple => 1,
            default => 2,
        };

        return $value->drop($drop)->dropEnd(1);
    }

    public function cleanExplode(Str $value): Str
    {
        return $this->clean($value)->dropEnd(1);
    }

    public function matches(Str $value): bool
    {
        return $value->matches("~^\{{$this->regex()}[a-zA-Z0-9_]+\}\$~");
    }

    public function matchesExplode(Str $value): bool
    {
        return $value->matches("~^\{{$this->regex()}[a-zA-Z0-9_]+\*\}\$~");
    }

    public function matchesLimit(Str $value): bool
    {
        return $value->matches("~^\{{$this->regex()}[a-zA-Z0-9_]+:\d+\}\$~");
    }

    public function matchesMany(Str $value): bool
    {
        return $value->matches("~^\{{$this->regex()}[a-zA-Z0-9_]+(,[a-zA-Z0-9_]+)*\}\$~");
    }

    private function regex(): string
    {
        return match ($this) {
            self::simple => '',
            default => '\\'.$this->toString(),
        };
    }

    private function toString(): string
    {
        return match ($this) {
            self::simple => '',
            self::reserved => '+',
            self::fragment => '#',
            self::label => '.',
            self::path => '/',
            self::parameter => ';',
            self::query => '?',
            self::queryContinuation => '&',
        };
    }
}
