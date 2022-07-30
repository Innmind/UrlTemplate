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

/**
 * @psalm-immutable
 */
final class Parameters implements Expression
{
    private Expression $expression;

    /**
     * @no-named-arguments
     */
    public function __construct(Name ...$names)
    {
        $this->expression = NamedValues::keyOnlyWhenEmpty(';', ';', ...$names);
    }

    /**
     * @psalm-pure
     */
    public static function of(Str $string): Expression
    {
        if (!$string->matches('~^\{;[a-zA-Z0-9_]+(,[a-zA-Z0-9_]+)+\}$~')) {
            throw new DomainException($string->toString());
        }

        $names = $string
            ->trim('{;}')
            ->split(',')
            ->map(static fn($name) => new Name($name->toString()));

        return new self(...$names->toList());
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
