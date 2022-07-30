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
final class Query implements Expression
{
    private Expression $expression;

    /**
     * @param Sequence<Name> $names
     */
    private function __construct(Sequence $names)
    {
        $this->expression = new NamedValues('?', '&', $names);
    }

    /**
     * @psalm-pure
     */
    public static function of(Str $string): Expression
    {
        if (!$string->matches('~^\{\?[a-zA-Z0-9_]+(,[a-zA-Z0-9_]+)*\}$~')) {
            throw new DomainException($string->toString());
        }

        return new self(
            $string
                ->trim('{?}')
                ->split(',')
                ->map(static fn($name) => $name->toString())
                ->map(Name::of(...)),
        );
    }

    /**
     * @psalm-pure
     */
    public static function named(Name $name): self
    {
        return new self(Sequence::of($name));
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
