<?php
declare(strict_types = 1);

namespace Innmind\UrlTemplate\Expression\Level3;

use Innmind\UrlTemplate\{
    Expression,
    Expression\Name,
    Exception\DomainException,
};
use Innmind\Immutable\{
    Map,
    Sequence,
    Str,
};
use function Innmind\Immutable\unwrap;

final class Query implements Expression
{
    private Expression $expression;

    public function __construct(Name ...$names)
    {
        $this->expression = new NamedValues('?', '&', ...$names);
    }

    public static function of(Str $string): Expression
    {
        if (!$string->matches('~^\{\?[a-zA-Z0-9_]+(,[a-zA-Z0-9_]+)+\}$~')) {
            throw new DomainException($string->toString());
        }

        /** @var Sequence<Name> $names */
        $names = $string
            ->trim('{?}')
            ->split(',')
            ->reduce(
                Sequence::of(Name::class),
                static fn(Sequence $names, Str $name): Sequence => ($names)(new Name($name->toString())),
            );

        return new self(...unwrap($names));
    }

    public function expand(Map $variables): string
    {
        return $this->expression->expand($variables);
    }

    public function regex(): string
    {
        return $this->expression->regex();
    }

    public function toString(): string
    {
        return $this->expression->toString();
    }
}
