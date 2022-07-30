<?php
declare(strict_types = 1);

namespace Innmind\UrlTemplate\Expression;

use Innmind\UrlTemplate\{
    Expression,
    Exception\DomainException,
};
use Innmind\Immutable\{
    Map,
    Sequence,
    Str,
};

final class Level3 implements Expression
{
    /** @var Sequence<Name> */
    private Sequence $names;
    /** @var Sequence<Level1> */
    private Sequence $expressions;
    private ?string $regex = null;
    private ?string $string = null;

    /**
     * @no-named-arguments
     */
    public function __construct(Name ...$names)
    {
        $this->names = Sequence::of(...$names);
        $this->expressions = $this->names->map(
            static fn(Name $name) => new Level1($name),
        );
    }

    public static function of(Str $string): Expression
    {
        if (!$string->matches('~^\{[a-zA-Z0-9_]+(,[a-zA-Z0-9_]+)+\}$~')) {
            throw new DomainException($string->toString());
        }

        $names = $string
            ->trim('{}')
            ->split(',')
            ->map(static fn($name) => new Name($name->toString()));

        return new self(...$names->toList());
    }

    public function expand(Map $variables): string
    {
        $expanded = $this->expressions->map(
            static fn($expression) => $expression->expand($variables),
        );

        return Str::of(',')->join($expanded)->toString();
    }

    public function regex(): string
    {
        /** @psalm-suppress InvalidArgument */
        return $this->regex ?? $this->regex = Str::of(',')
            ->join($this->names->map(
                static fn(Name $name) => "(?<{$name->toString()}>[a-zA-Z0-9\%\-\.\_\~]*)",
            ))
            ->toString();
    }

    public function toString(): string
    {
        return $this->string ?? $this->string = Str::of(',')
            ->join($this->names->map(
                static fn($name) => $name->toString(),
            ))
            ->prepend('{')
            ->append('}')
            ->toString();
    }
}
