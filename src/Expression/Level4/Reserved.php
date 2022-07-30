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

/**
 * @psalm-immutable
 */
final class Reserved implements Expression
{
    private Name $name;
    private ?int $limit = null;
    private bool $explode = false;
    private Expression $expression;

    private function __construct(Name $name)
    {
        $this->name = $name;
        $this->expression = Level4::named($name)->withExpression(
            Level2\Reserved::named(...),
        );
    }

    /**
     * @psalm-pure
     */
    public static function of(Str $string): Expression
    {
        if ($string->matches('~^\{\+[a-zA-Z0-9_]+\}$~')) {
            return new self(Name::of($string->trim('{+}')->toString()));
        }

        if ($string->matches('~^\{\+[a-zA-Z0-9_]+\*\}$~')) {
            return self::explode(Name::of($string->trim('{+*}')->toString()));
        }

        if ($string->matches('~^\{\+[a-zA-Z0-9_]+:\d+\}$~')) {
            $string = $string->trim('{+}');
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
        $self->limit = $limit;
        $self->expression = Level4::limit($name, $limit)->withExpression(
            Level2\Reserved::named(...),
        );

        return $self;
    }

    /**
     * @psalm-pure
     */
    public static function explode(Name $name): self
    {
        $self = new self($name);
        $self->explode = true;
        $self->expression = Level4::explode($name)->withExpression(
            Level2\Reserved::named(...),
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

    public function expand(Map $variables): string
    {
        return $this->expression->expand($variables);
    }

    public function regex(): string
    {
        if ($this->explode) {
            throw new ExplodeExpressionCantBeMatched;
        }

        if (\is_int($this->limit)) {
            return "(?<{$this->name->toString()}>[a-zA-Z0-9\%:/\?#\[\]@!\$&'\(\)\*\+,;=\-\.\_\~]{{$this->limit}})";
        }

        return $this->expression->regex();
    }

    public function toString(): string
    {
        if (\is_int($this->limit)) {
            return "{+{$this->name->toString()}:{$this->limit}}";
        }

        if ($this->explode) {
            return "{+{$this->name->toString()}*}";
        }

        return "{+{$this->name->toString()}}";
    }
}
