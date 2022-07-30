<?php
declare(strict_types = 1);

namespace Innmind\UrlTemplate\Expression;

use Innmind\UrlTemplate\Exception\DomainException;
use Innmind\Immutable\Str;

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

    public function toString(): string
    {
        return $this->value;
    }
}
