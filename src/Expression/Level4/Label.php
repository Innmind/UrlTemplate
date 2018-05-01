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
use Innmind\Immutable\MapInterface;

final class Label implements Expression
{
    private $expression;

    public function __construct(Name $name)
    {
        $this->expression = (new Level4($name))->withLead('.');

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
