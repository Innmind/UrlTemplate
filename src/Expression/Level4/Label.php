<?php
declare(strict_types = 1);

namespace Innmind\UrlTemplate\Expression\Level4;

use Innmind\UrlTemplate\{
    Expression,
    Expression\Name,
    Expression\Level1,
    Expression\Level4,
    Exception\DomainException,
};
use Innmind\Immutable\{
    MapInterface,
    Str,
};

final class Label implements Expression
{
    private $expression;

    public function __construct(Name $name)
    {
        $this->expression = (new Level4($name))->withLead('.');

    }

    /**
     * {@inheritdoc}
     */
    public static function of(Str $string): Expression
    {
        if ($string->matches('~^\{\.[a-zA-Z0-9_]+\}$~')) {
            return new self(new Name((string) $string->trim('{.}')));
        }

        if ($string->matches('~^\{\.[a-zA-Z0-9_]+\*\}$~')) {
            return self::explode(new Name((string) $string->trim('{.*}')));
        }

        if ($string->matches('~^\{\.[a-zA-Z0-9_]+:\d+\}$~')) {
            $string = $string->trim('{.}');
            [$name, $limit] = $string->split(':');

            return self::limit(
                new Name((string) $name),
                (int) (string) $limit
            );
        }

        throw new DomainException((string) $string);
    }

    public static function limit(Name $name, int $limit): self
    {
        if ($limit < 0) {
            throw new DomainException;
        }

        $self = new self($name);
        $self->expression = Level4::limit($name, $limit)->withLead('.');

        return $self;
    }

    public static function explode(Name $name): self
    {
        $self = new self($name);
        $self->expression = Level4::explode($name)
            ->withLead('.')
            ->withSeparator('.');

        return $self;
    }

    public function add(Str $pattern): Composite
    {
        return new Composite(
            '',
            $this,
            self::of($pattern->prepend('{.')->append('}'))
        );
    }

    /**
     * {@inheritdoc}
     */
    public function expand(MapInterface $variables): string
    {
        return $this->expression->expand($variables);
    }

    public function __toString(): string
    {
        return (string) $this->expression;
    }
}
