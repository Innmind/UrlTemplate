<?php
declare(strict_types = 1);

namespace Innmind\UrlTemplate\Expression;

use Innmind\UrlTemplate\{
    Expression,
    Exception\DomainException,
};
use Innmind\Immutable\{
    Map,
    Sequence,
    Str,
};
use function Innmind\Immutable\{
    unwrap,
    join,
};

final class Level3 implements Expression
{
    /** @var Sequence<Name> */
    private Sequence $names;
    /** @var Sequence<Level1> */
    private Sequence $expressions;
    private ?string $regex = null;
    private ?string $string = null;

    public function __construct(Name ...$names)
    {
        $this->names = Sequence::of(Name::class, ...$names);
        $this->expressions = $this
            ->names
            ->toSequenceOf(
                Level1::class,
                static fn($name): \Generator => yield new Level1($name),
            );
    }

    /**
     * {@inheritdoc}
     */
    public static function of(Str $string): Expression
    {
        if (!$string->matches('~^\{[a-zA-Z0-9_]+(,[a-zA-Z0-9_]+)+\}$~')) {
            throw new DomainException($string->toString());
        }

        $names = $string
            ->trim('{}')
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

        return join(',', $expanded)->toString();
    }

    public function regex(): string
    {
        return $this->regex ?? $this->regex = join(
            ',',
            $this->names->toSequenceOf(
                'string',
                static fn(Name $name): \Generator => yield "(?<{$name}>[a-zA-Z0-9\%\-\.\_\~]*)",
            ),
        )->toString();
    }

    public function __toString(): string
    {
        return $this->string ?? $this->string = join(
            ',',
            $this->names->toSequenceOf(
                'string',
                static fn($name): \Generator => yield (string) $name,
            ),
        )
            ->prepend('{')
            ->append('}')
            ->toString();
    }
}
