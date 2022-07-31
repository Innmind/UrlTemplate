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
    Map,
    Str,
    Maybe,
};

/**
 * @psalm-immutable
 */
final class Label implements Expression
{
    private Expression $expression;

    private function __construct(Name $name)
    {
        $this->expression = Level4::named($name)->withLead('.');
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
            '.',
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
        $self->expression = Level4::limit($name, $limit)->withLead('.');

        return $self;
    }

    /**
     * @psalm-pure
     */
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
            self::of($pattern->prepend('{.')->append('}'))->match(
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
        return $this->expression->regex();
    }

    public function toString(): string
    {
        return $this->expression->toString();
    }
}
