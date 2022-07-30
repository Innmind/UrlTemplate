<?php
declare(strict_types = 1);

namespace Innmind\UrlTemplate\Expression\Level3;

use Innmind\UrlTemplate\{
    Expression,
    Expression\Name,
    Expression\Level2,
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
final class Fragment implements Expression
{
    /** @var Sequence<Name> */
    private Sequence $names;
    /** @var Sequence<Expression> */
    private Sequence $expressions;

    /**
     * @no-named-arguments
     */
    public function __construct(Name ...$names)
    {
        $this->names = Sequence::of(...$names);
        /** @var Sequence<Expression> */
        $this->expressions = $this->names->map(
            static fn(Name $name) => new Level2\Reserved($name),
        );
    }

    /**
     * @psalm-pure
     */
    public static function of(Str $string): Expression
    {
        if (!$string->matches('~^\{#[a-zA-Z0-9_]+(,[a-zA-Z0-9_]+)+\}$~')) {
            throw new DomainException($string->toString());
        }

        $names = $string
            ->trim('{#}')
            ->split(',')
            ->map(static fn($name) => new Name($name->toString()));

        return new self(...$names->toList());
    }

    public function expand(Map $variables): string
    {
        $expanded = $this->expressions->map(
            static fn($expression) => $expression->expand($variables),
        );

        return Str::of(',')
            ->join($expanded)
            ->prepend('#')
            ->toString();
    }

    public function regex(): string
    {
        return Str::of(',')
            ->join($this->expressions->map(
                static fn($expression) => $expression->regex(),
            ))
            ->prepend('\#')
            ->toString();
    }

    public function toString(): string
    {
        return Str::of(',')
            ->join($this->names->map(
                static fn($element) => $element->toString(),
            ))
            ->prepend('{#')
            ->append('}')
            ->toString();
    }
}
