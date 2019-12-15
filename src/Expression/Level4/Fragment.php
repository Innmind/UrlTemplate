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
};
use Innmind\Immutable\{
    Map,
    Str,
};
use function Innmind\Immutable\unwrap;

final class Fragment implements Expression
{
    private Expression $expression;

    public function __construct(Name $name)
    {
        $this->expression = (new Level4($name))
            ->withLead('#')
            ->withExpression(Level2\Reserved::class);
    }

    /**
     * {@inheritdoc}
     */
    public static function of(Str $string): Expression
    {
        if ($string->matches('~^\{#[a-zA-Z0-9_]+\}$~')) {
            return new self(new Name($string->trim('{#}')->toString()));
        }

        if ($string->matches('~^\{#[a-zA-Z0-9_]+\*\}$~')) {
            return self::explode(new Name($string->trim('{#*}')->toString()));
        }

        if ($string->matches('~^\{#[a-zA-Z0-9_]+:\d+\}$~')) {
            $string = $string->trim('{#}');
            [$name, $limit] = unwrap($string->split(':'));

            return self::limit(
                new Name($name->toString()),
                (int) $limit->toString()
            );
        }

        throw new DomainException($string->toString());
    }

    public static function limit(Name $name, int $limit): self
    {
        if ($limit < 0) {
            throw new DomainException;
        }

        $self = new self($name);
        $self->expression = Level4::limit($name, $limit)
            ->withLead('#')
            ->withExpression(Level2\Reserved::class);

        return $self;
    }

    public function add(Str $pattern): Composite
    {
        return Composite::removeLead(
            ',',
            $this,
            self::of($pattern->prepend('{#')->append('}'))
        );
    }

    public static function explode(Name $name): self
    {
        $self = new self($name);
        $self->expression = Level4::explode($name)
            ->withLead('#')
            ->withExpression(Level2\Reserved::class);

        return $self;
    }

    public function regex(): string
    {
        return $this->expression->regex();
    }

    /**
     * {@inheritdoc}
     */
    public function expand(Map $variables): string
    {
        return $this->expression->expand($variables);
    }

    public function __toString(): string
    {
        return (string) $this->expression;
    }
}
