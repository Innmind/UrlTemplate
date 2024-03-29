<?php
declare(strict_types = 1);

namespace Innmind\UrlTemplate\Expression\Level4;

use Innmind\UrlTemplate\{
    Expression,
    Expression\Name,
    Expression\Expansion,
    Expression\Level2,
    Expression\Level4,
    Exception\DomainException,
    Exception\LogicException,
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
    private Expression $expression;

    private function __construct(Name $name)
    {
        $this->expression = Level4::named($name)
            ->withExpansion(Expansion::fragment)
            ->withExpression(Level2\Reserved::named(...));
    }

    /**
     * @psalm-pure
     */
    public static function of(Str $string): Maybe
    {
        return Parse::of(
            $string,
            static fn(Name $name) => new self($name),
            self::explode(...),
            self::limit(...),
            Expansion::fragment,
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
        $self->expression = Level4::limit($name, $limit)
            ->withExpansion(Expansion::fragment)
            ->withExpression(Level2\Reserved::named(...));

        return $self;
    }

    /**
     * @psalm-pure
     */
    public static function explode(Name $name): self
    {
        $self = new self($name);
        $self->expression = Level4::explode($name)
            ->withExpansion(Expansion::fragment)
            ->withExpression(Level2\Reserved::named(...));

        return $self;
    }

    public function expansion(): Expansion
    {
        return Expansion::fragment;
    }

    public function regex(): string
    {
        return $this->expression->regex();
    }

    public function expand(Map $variables): string
    {
        return $this->expression->expand($variables);
    }

    public function toString(): string
    {
        return $this->expression->toString();
    }
}
