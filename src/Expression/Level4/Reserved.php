<?php
declare(strict_types = 1);

namespace Innmind\UrlTemplate\Expression\Level4;

use Innmind\UrlTemplate\{
    Expression,
    Expression\Name,
    Expression\Level2,
    Expression\Level4,
    Exception\DomainException,
};
use Innmind\Immutable\{
    MapInterface,
    Str,
};

final class Reserved implements Expression
{
    private $name;
    private $limit;
    private $explode;
    private $expression;

    public function __construct(Name $name)
    {
        $this->name = $name;
        $this->expression = (new Level4($name))->withExpression(
            Level2\Reserved::class
        );

    }

    /**
     * {@inheritdoc}
     */
    public static function of(Str $string): Expression
    {
        if ($string->matches('~\{\+[a-zA-Z0-9_]+\}~')) {
            return new self(new Name((string) $string->trim('{+}')));
        }

        if ($string->matches('~\{\+[a-zA-Z0-9_]+\*\}~')) {
            return self::explode(new Name((string) $string->trim('{+*}')));
        }

        if ($string->matches('~\{\+[a-zA-Z0-9_]+:\d+\}~')) {
            $string = $string->trim('{+}');
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
        $self->limit = $limit;
        $self->expression = Level4::limit($name, $limit)->withExpression(
            Level2\Reserved::class
        );

        return $self;
    }

    public static function explode(Name $name): self
    {
        $self = new self($name);
        $self->explode = true;
        $self->expression = Level4::explode($name)->withExpression(
            Level2\Reserved::class
        );

        return $self;
    }

    public function add(Str $pattern): Composite
    {
        return new Composite(
            ',',
            $this,
            self::of($pattern->prepend('{+')->append('}'))
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
        if (is_int($this->limit)) {
            return "{+{$this->name}:{$this->limit}}";
        }

        if ($this->explode) {
            return "{+{$this->name}*}";
        }

        return "{+{$this->name}}";
    }
}
