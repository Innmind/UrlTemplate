<?php
declare(strict_types = 1);

namespace Innmind\UrlTemplate\Expression\Level3;

use Innmind\UrlTemplate\{
    Expression,
    Expression\Name,
    Expression\Level1,
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

final class Label implements Expression
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
        $this->expressions = $this->names->mapTo(
            Expression::class,
            static fn($name) => new Level1($name),
        );
    }

    /**
     * {@inheritdoc}
     */
    public static function of(Str $string): Expression
    {
        if (!$string->matches('~^\{\.[a-zA-Z0-9_]+(,[a-zA-Z0-9_]+)+\}$~')) {
            throw new DomainException($string->toString());
        }

        $names = $string
            ->trim('{.}')
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
        $expanded = $this->expressions->mapTo(
            'string',
            static fn($expression) => $expression->expand($variables),
        );

        return join('.', $expanded)
            ->prepend('.')
            ->toString();
    }

    public function regex(): string
    {
        return $this->regex ?? $this->regex = join(
            '.',
            $this->expressions->mapTo(
                'string',
                static fn($expression) => $expression->regex(),
            ),
        )
            ->replace('\.', '')
            ->prepend('\.')
            ->toString();
    }

    public function __toString(): string
    {
        return $this->string ?? $this->string = join(
            ',',
            $this->names->mapTo(
                'string',
                static fn($element) => (string) $element,
            ),
        )
            ->prepend('{.')
            ->append('}')
            ->toString();
    }
}
