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

final class Reserved implements Expression
{
    /** @var Sequence<Name> */
    private Sequence $names;
    /** @var Sequence<Expression> */
    private Sequence $expressions;
    private ?string $regex = null;
    private ?string $string = null;

    public function __construct(Name ...$names)
    {
        /** @var Sequence<Name> */
        $this->names = Sequence::of(Name::class, ...$names);
        /** @var Sequence<Expression> */
        $this->expressions = $this->names->mapTo(
            Expression::class,
            static fn(Name $name) => new Level2\Reserved($name),
        );
    }

    public static function of(Str $string): Expression
    {
        if (!$string->matches('~^\{\+[a-zA-Z0-9_]+(,[a-zA-Z0-9_]+)+\}$~')) {
            throw new DomainException($string->toString());
        }

        /** @var Sequence<Name> $names */
        $names = $string
            ->trim('{+}')
            ->split(',')
            ->reduce(
                Sequence::of(Name::class),
                static fn(Sequence $names, Str $name): Sequence => ($names)(new Name($name->toString())),
            );

        return new self(...unwrap($names));
    }

    public function expand(Map $variables): string
    {
        $expanded = $this->expressions->mapTo(
            'string',
            static fn($expression) => $expression->expand($variables),
        );

        return join(',', $expanded)->toString();
    }

    public function regex(): string
    {
        return $this->regex ?? $this->regex = join(
            ',',
            $this->expressions->mapTo(
                'string',
                static fn($expression) => $expression->regex(),
            ),
        )->toString();
    }

    public function toString(): string
    {
        return $this->string ?? $this->string = join(
            ',',
            $this->names->mapTo(
                'string',
                static fn($element) => $element->toString(),
            ),
        )
            ->prepend('{+')
            ->append('}')
            ->toString();
    }
}
