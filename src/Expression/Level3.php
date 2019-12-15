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
    private Sequence $names;
    private Sequence $expressions;
    private ?string $regex = null;
    private ?string $string = null;

    public function __construct(Name ...$names)
    {
        $this->names = Sequence::mixed(...$names);
        $this->expressions = $this->names->map(static function(Name $name): Level1 {
            return new Level1($name);
        });
    }

    /**
     * {@inheritdoc}
     */
    public static function of(Str $string): Expression
    {
        if (!$string->matches('~^\{[a-zA-Z0-9_]+(,[a-zA-Z0-9_]+)+\}$~')) {
            throw new DomainException($string->toString());
        }

        return new self(
            ...unwrap($string
                ->trim('{}')
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
        )->toString();
    }

    public function regex(): string
    {
        return $this->regex ?? $this->regex = join(
            ',',
            $this
                ->names
                ->map(static function(Name $name): string {
                    return "(?<{$name}>[a-zA-Z0-9\%\-\.\_\~]*)";
                })
                ->toSequenceOf('string'),
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
