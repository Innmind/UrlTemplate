<?php
declare(strict_types = 1);

namespace Innmind\UrlTemplate\Expression\Level3;

use Innmind\UrlTemplate\{
    Expression,
    Expression\Name,
    Expression\Level2,
    Exception\DomainException,
};
use Innmind\Immutable\{
    Map,
    Sequence,
    Str,
};
use function Innmind\Immutable\{
    join,
    unwrap,
};

final class Fragment implements Expression
{
    private Sequence $names;
    private Sequence $expressions;
    private ?string $regex = null;
    private ?string $string = null;

    public function __construct(Name ...$names)
    {
        $this->names = Sequence::mixed(...$names);
        $this->expressions = $this->names->map(static function(Name $name): Level2\Reserved {
            return new Level2\Reserved($name);
        });
    }

    /**
     * {@inheritdoc}
     */
    public static function of(Str $string): Expression
    {
        if (!$string->matches('~^\{#[a-zA-Z0-9_]+(,[a-zA-Z0-9_]+)+\}$~')) {
            throw new DomainException($string->toString());
        }

        return new self(
            ...unwrap($string
                ->trim('{#}')
                ->split(',')
                ->reduce(
                    Sequence::mixed(),
                    static function(Sequence $names, Str $name): Sequence {
                        return $names->add(new Name($name->toString()));
                    }
                ))
        );
    }

    /**
     * {@inheritdoc}
     */
    public function expand(Map $variables): string
    {
        return join(
            ',',
            $this
                ->expressions
                ->map(static function(Expression $expression) use ($variables): string {
                    return $expression->expand($variables);
                })
                ->toSequenceOf('string'),
        )
            ->prepend('#')
            ->toString();
    }

    public function regex(): string
    {
        return $this->regex ?? $this->regex = join(
            ',',
            $this
                ->expressions
                ->map(static function(Expression $expression): string {
                    return $expression->regex();
                })
                ->toSequenceOf('string'),
        )
            ->prepend('\#')
            ->toString();
    }

    public function __toString(): string
    {
        return $this->string ?? $this->string = join(
            ',',
            $this->names->toSequenceOf(
                'string',
                static fn($element): \Generator => yield (string) $element,
            ),
        )
            ->prepend('{#')
            ->append('}')
            ->toString();
    }
}
