<?php
declare(strict_types = 1);

namespace Innmind\UrlTemplate\Expression\Level2;

use Innmind\UrlTemplate\{
    Expression,
    Expression\Name,
    UrlEncode,
    Exception\OnlyScalarCanBeExpandedForExpression,
};
use Innmind\Immutable\{
    Map,
    Str,
    Maybe,
};

/**
 * @psalm-immutable
 */
final class Fragment implements Expression
{
    private Name $name;
    private UrlEncode $encode;

    private function __construct(Name $name)
    {
        $this->name = $name;
        $this->encode = UrlEncode::allowReservedCharacters();
    }

    /**
     * @psalm-pure
     */
    public static function of(Str $string): Maybe
    {
        /** @var Maybe<Expression> */
        return Name::one($string, '#')->map(static fn($name) => new self($name));
    }

    public function expand(Map $variables): string
    {
        return $variables
            ->get($this->name->toString())
            ->match(
                function($variable) {
                    if (\is_array($variable)) {
                        throw new OnlyScalarCanBeExpandedForExpression($this->name->toString());
                    }

                    return '#'.($this->encode)((string) $variable);
                },
                static fn() => '',
            );
    }

    public function regex(): string
    {
        return "\#(?<{$this->name->toString()}>[a-zA-Z0-9\%:/\?#\[\]@!\$&'\(\)\*\+,;=\-\.\_\~]*)";
    }

    public function toString(): string
    {
        return "{#{$this->name->toString()}}";
    }
}
