<?php
declare(strict_types = 1);

namespace Innmind\UrlTemplate\Expression\Level4;

use Innmind\UrlTemplate\{
    Expression,
    Expression\Name,
    Expression\Level2,
    Expression\Level4,
    Exception\DomainException,
    Exception\ExplodeExpressionCantBeMatched,
    Exception\ExpressionLimitCantBeNegative,
};
use Innmind\Immutable\{
    Map,
    Str,
};
use function Innmind\Immutable\unwrap;

final class Reserved implements Expression
{
    private Name $name;
    private ?int $limit = null;
    private bool $explode = false;
    private Expression $expression;
    private ?string $regex = null;
    private ?string $string = null;

    public function __construct(Name $name)
    {
        $this->name = $name;
        $this->expression = (new Level4($name))->withExpression(
            Level2\Reserved::class,
        );
    }

    /**
     * {@inheritdoc}
     */
    public static function of(Str $string): Expression
    {
        if ($string->matches('~^\{\+[a-zA-Z0-9_]+\}$~')) {
            return new self(new Name($string->trim('{+}')->toString()));
        }

        if ($string->matches('~^\{\+[a-zA-Z0-9_]+\*\}$~')) {
            return self::explode(new Name($string->trim('{+*}')->toString()));
        }

        if ($string->matches('~^\{\+[a-zA-Z0-9_]+:\d+\}$~')) {
            $string = $string->trim('{+}');
            [$name, $limit] = unwrap($string->split(':'));

            return self::limit(
                new Name($name->toString()),
                (int) $limit->toString(),
            );
        }

        throw new DomainException($string->toString());
    }

    public static function limit(Name $name, int $limit): self
    {
        if ($limit < 0) {
            throw new ExpressionLimitCantBeNegative($limit);
        }

        $self = new self($name);
        $self->limit = $limit;
        $self->expression = Level4::limit($name, $limit)->withExpression(
            Level2\Reserved::class,
        );

        return $self;
    }

    public static function explode(Name $name): self
    {
        $self = new self($name);
        $self->explode = true;
        $self->expression = Level4::explode($name)->withExpression(
            Level2\Reserved::class,
        );

        return $self;
    }

    public function add(Str $pattern): Composite
    {
        return new Composite(
            ',',
            $this,
            self::of($pattern->prepend('{+')->append('}')),
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
        if (\is_string($this->regex)) {
            return $this->regex;
        }

        if ($this->explode) {
            throw new ExplodeExpressionCantBeMatched;
        }

        if (\is_int($this->limit)) {
            return $this->regex = "(?<{$this->name->toString()}>[a-zA-Z0-9\%:/\?#\[\]@!\$&'\(\)\*\+,;=\-\.\_\~]{{$this->limit}})";
        }

        return $this->regex = $this->expression->regex();
    }

    public function toString(): string
    {
        if (\is_string($this->string)) {
            return $this->string;
        }

        if (\is_int($this->limit)) {
            return $this->string = "{+{$this->name->toString()}:{$this->limit}}";
        }

        if ($this->explode) {
            return $this->string = "{+{$this->name->toString()}*}";
        }

        return $this->string = "{+{$this->name->toString()}}";
    }
}
