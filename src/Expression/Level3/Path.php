<?php
declare(strict_types = 1);

namespace Innmind\UrlTemplate\Expression\Level3;

use Innmind\UrlTemplate\{
    Expression,
    Expression\Name,
    Expression\Level1,
    Exception\DomainException,
};
use Innmind\Immutable\{
    Map,
    Sequence,
    Str,
};

/**
 * @psalm-immutable
 */
final class Path implements Expression
{
    /** @var Sequence<Name> */
    private Sequence $names;
    /** @var Sequence<Expression> */
    private Sequence $expressions;

    /**
     * @param Sequence<Name> $names
     */
    public function __construct(Sequence $names)
    {
        $this->names = $names;
        /** @var Sequence<Expression> */
        $this->expressions = $this->names->map(
            static fn(Name $name) => new Level1($name),
        );
    }

    /**
     * @psalm-pure
     */
    public static function of(Str $string): Expression
    {
        if (!$string->matches('~^\{/[a-zA-Z0-9_]+(,[a-zA-Z0-9_]+)+\}$~')) {
            throw new DomainException($string->toString());
        }

        return new self(
            $string
                ->trim('{/}')
                ->split(',')
                ->map(static fn($name) => new Name($name->toString())),
        );
    }

    public function expand(Map $variables): string
    {
        return Str::of('/')
            ->join($this->expressions->map(
                static fn($expression) => $expression->expand($variables),
            ))
            ->prepend('/')
            ->toString();
    }

    public function regex(): string
    {
        return Str::of('/')
            ->join($this->expressions->map(
                static fn($expression) => $expression->regex(),
            ))
            ->prepend('/')
            ->toString();
    }

    public function toString(): string
    {
        return Str::of(',')
            ->join($this->names->map(
                static fn($element) => $element->toString(),
            ))
            ->prepend('{/')
            ->append('}')
            ->toString();
    }
}
