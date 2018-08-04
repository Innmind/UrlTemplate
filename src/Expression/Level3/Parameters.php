<?php
declare(strict_types = 1);

namespace Innmind\UrlTemplate\Expression\Level3;

use Innmind\UrlTemplate\{
    Expression,
    Expression\Name,
    Exception\DomainException,
};
use Innmind\Immutable\{
    MapInterface,
    SequenceInterface,
    Sequence,
    Str,
};

final class Parameters implements Expression
{
    private $expression;

    public function __construct(Name ...$names)
    {
        $this->expression = NamedValues::keyOnlyWhenEmpty(';', ';', ...$names);
    }

    /**
     * {@inheritdoc}
     */
    public static function of(Str $string): Expression
    {
        if (!$string->matches('~^\{;[a-zA-Z0-9_]+(,[a-zA-Z0-9_]+)+\}$~')) {
            throw new DomainException((string) $string);
        }

        return new self(
            ...$string
                ->trim('{;}')
                ->split(',')
                ->reduce(
                    new Sequence,
                    static function(SequenceInterface $names, Str $name): SequenceInterface {
                        return $names->add(new Name((string) $name));
                    }
                )
        );
    }

    /**
     * {@inheritdoc}
     */
    public function expand(MapInterface $variables): string
    {
        return $this->expression->expand($variables);
    }

    public function regex(): string
    {
        return $this->expression->regex();
    }

    public function __toString(): string
    {
        return (string) $this->expression;
    }
}
