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
    /** @var Sequence<Name> */
    private Sequence $names;
    /** @var Sequence<Expression> */
    private Sequence $expressions;
    private ?string $regex = null;
    private ?string $string = null;

    public function __construct(Name ...$names)
    {
        $this->names = Sequence::of(Name::class, ...$names);
        $this->expressions = $this->names->toSequenceOf(
            Expression::class,
            static fn($name): \Generator => yield new Level2\Reserved($name),
        );
    }

    /**
     * {@inheritdoc}
     */
    public static function of(Str $string): Expression
    {
        if (!$string->matches('~^\{#[a-zA-Z0-9_]+(,[a-zA-Z0-9_]+)+\}$~')) {
            throw new DomainException($string->toString());
        }

        $names = $string
            ->trim('{#}')
            ->split(',')
            ->reduce(
                Sequence::of(Name::class),
                static fn(Sequence $names, Str $name): Sequence => ($names)(new Name($name->toString())),
            );

        return new self(
            ...unwrap($names),
        );
    }

    /**
     * {@inheritdoc}
     */
    public function expand(Map $variables): string
    {
        $expanded = $this->expressions->toSequenceOf(
            'string',
            static fn($expression): \Generator => yield $expression->expand($variables),
        );

        return join(',', $expanded)
            ->prepend('#')
            ->toString();
    }

    public function regex(): string
    {
        return $this->regex ?? $this->regex = join(
            ',',
            $this->expressions->toSequenceOf(
                'string',
                static fn($expression): \Generator => yield $expression->regex(),
            ),
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
