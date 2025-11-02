<?php
declare(strict_types = 1);

namespace Innmind\UrlTemplate\Expression\Level4;

use Innmind\UrlTemplate\{
    Expression,
    Expression\Name,
    Expression\Expansion,
    Expression\Level4,
};
use Innmind\Immutable\{
    Map,
    Str,
    Attempt,
};

/**
 * @psalm-immutable
 * @internal
 */
final class Label implements Expression
{
    private Expression $expression;

    private function __construct(Name $name)
    {
        $this->expression = Level4::named($name)->withExpansion(Expansion::label);
    }

    /**
     * @psalm-pure
     *
     * @return Attempt<self>
     */
    public static function of(Str $string): Attempt
    {
        return Parse::of(
            $string,
            static fn(Name $name) => new self($name),
            self::explode(...),
            self::limit(...),
            Expansion::label,
        );
    }

    /**
     * @psalm-pure
     *
     * @param positive-int $limit
     */
    public static function limit(Name $name, int $limit): self
    {
        $self = new self($name);
        $self->expression = Level4::limit($name, $limit)->withExpansion(Expansion::label);

        return $self;
    }

    /**
     * @psalm-pure
     */
    public static function explode(Name $name): self
    {
        $self = new self($name);
        $self->expression = Level4::explode($name)
            ->withExpansion(Expansion::label);

        return $self;
    }

    #[\Override]
    public function expansion(): Expansion
    {
        return Expansion::label;
    }

    #[\Override]
    public function expand(Map $values, Map $lists, Map $keys): string
    {
        return $this->expression->expand($values, $lists, $keys);
    }

    #[\Override]
    public function regex(): string
    {
        return $this->expression->regex();
    }

    #[\Override]
    public function toString(): string
    {
        return $this->expression->toString();
    }
}
