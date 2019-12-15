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

final class QueryContinuation implements Expression
{
    private Expression $expression;

    public function __construct(Name ...$names)
    {
        $this->expression = new NamedValues('&', '&', ...$names);
    }

    /**
     * {@inheritdoc}
     */
    public static function of(Str $string): Expression
    {
        if (!$string->matches('~^\{\&[a-zA-Z0-9_]+(,[a-zA-Z0-9_]+)+\}$~')) {
            throw new DomainException($string->toString());
        }

        return new self(
            ...unwrap($string
                ->trim('{&}')
                ->split(',')
                ->reduce(
                    Sequence::mixed(),
                    static function(Sequence $names, Str $name): Sequence {
                        return $names->add(new Name($name->toString()));
                    }
                ))
        );
    }

    /**
     * {@inheritdoc}
     */
    public function expand(Map $variables): string
    {
        return $this->expression->expand($variables);
    }

    public function regex(): string
    {
        return $this->expression->regex();
    }

    public function __toString(): string
    {
        return (string) $this->expression;
    }
}
