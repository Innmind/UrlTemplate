<?php
declare(strict_types = 1);

namespace Innmind\UrlTemplate\Expression\Level4;

use Innmind\UrlTemplate\{
    Expression,
    Expression\Name,
    Expression\Level2,
    Expression\Level4,
    Exception\DomainException,
    Exception\LogicException,
    Exception\ExpressionLimitCantBeNegative,
};
use Innmind\Immutable\{
    Map,
    Str,
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
            ->withLead('#')
            ->withExpression(Level2\Reserved::named(...));
    }

    /**
     * @psalm-pure
     */
    public static function of(Str $string): Expression
    {
        if ($string->matches('~^\{#[a-zA-Z0-9_]+\}$~')) {
            return new self(Name::of($string->trim('{#}')->toString()));
        }

        if ($string->matches('~^\{#[a-zA-Z0-9_]+\*\}$~')) {
            return self::explode(Name::of($string->trim('{#*}')->toString()));
        }

        if ($string->matches('~^\{#[a-zA-Z0-9_]+:\d+\}$~')) {
            $string = $string->trim('{#}');
            [$name, $limit] = $string->split(':')->toList();

            return self::limit(
                Name::of($name->toString()),
                (int) $limit->toString(),
            );
        }

        throw new DomainException($string->toString());
    }

    /**
     * @psalm-pure
     */
    public static function limit(Name $name, int $limit): self
    {
        if ($limit < 0) {
            throw new ExpressionLimitCantBeNegative($limit);
        }

        $self = new self($name);
        $self->expression = Level4::limit($name, $limit)
            ->withLead('#')
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
            ->withLead('#')
            ->withExpression(Level2\Reserved::named(...));

        return $self;
    }

    public function add(Str $pattern): Composite
    {
        return Composite::removeLead(
            ',',
            $this,
            self::of($pattern->prepend('{#')->append('}')),
        );
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
