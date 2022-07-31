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
    Exception\ExplodeExpressionCantBeMatched,
};
use Innmind\Immutable\{
    Map,
    Str,
    Maybe,
};

/**
 * @psalm-immutable
 */
final class Reserved implements Expression
{
    private Name $name;
    /** @var ?positive-int */
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
    public static function of(Str $string): Maybe
    {
        return Parse::of(
            $string,
            static fn(Name $name) => new self($name),
            self::explode(...),
            self::limit(...),
            Expansion::reserved,
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

    public function expansion(): Expansion
    {
        return Expansion::reserved;
    }

    public function add(Str $pattern): Composite
    {
        return new Composite(
            ',',
            $this,
            self::of($pattern->prepend('{+')->append('}'))->match(
                static fn($expression) => $expression,
                static fn() => throw new DomainException('todo'),
            ),
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
