<?php
declare(strict_types = 1);

namespace Innmind\UrlTemplate\Expression\Level3;

use Innmind\UrlTemplate\{
    Expression,
    Expression\Name,
    Expression\Expansion,
    Expression\Level1,
};
use Innmind\Immutable\{
    Map,
    Sequence,
    Str,
    Maybe,
};

/**
 * @psalm-immutable
 */
final class NamedValues implements Expression
{
    private Expansion $expansion;
    /** @var Sequence<Name> */
    private Sequence $names;
    /** @var Map<string, Expression> */
    private Map $expressions;
    private bool $keyOnlyWhenEmpty = false;

    /**
     * @param Sequence<Name> $names
     */
    public function __construct(Expansion $expansion, Sequence $names)
    {
        $this->expansion = $expansion;
        $this->names = $names;
        /** @var Map<string, Expression> */
        $this->expressions = Map::of(
            ...$this
                ->names
                ->map(static fn($name) => [
                    $name->toString(),
                    Level1::named($name),
                ])
                ->toList(),
        );
    }

    /**
     * @psalm-pure
     */
    public static function of(Str $string): Maybe
    {
        throw new \LogicException('should not be used directly');
    }

    /**
     * @psalm-pure
     *
     * @param Sequence<Name> $names
     */
    public static function keyOnlyWhenEmpty(Expansion $expansion, Sequence $names): self
    {
        $self = new self($expansion, $names);
        $self->keyOnlyWhenEmpty = true;

        return $self;
    }

    public function expansion(): Expansion
    {
        return $this->expansion;
    }

    public function expand(Map $variables): string
    {
        /** @var Sequence<string> */
        $expanded = $this->expressions->reduce(
            Sequence::strings(),
            function(Sequence $expanded, string $name, Expression $expression) use ($variables): Sequence {
                $value = Str::of($expression->expand($variables));

                if ($value->empty() && $this->keyOnlyWhenEmpty) {
                    return ($expanded)($name);
                }

                return ($expanded)("$name={$value->toString()}");
            },
        );

        return Str::of($this->expansion->continuation()->toString())
            ->join($expanded)
            ->prepend($this->expansion->toString())
            ->toString();
    }

    public function regex(): string
    {
        return Str::of($this->expansion->continuation()->regex())
            ->join($this->names->map(
                fn($name) => \sprintf(
                    '%s=%s%s',
                    $name->toString(),
                    $this->keyOnlyWhenEmpty ? '?' : '',
                    Level1::named($name)->regex(),
                ),
            ))
            ->prepend($this->expansion->regex())
            ->toString();
    }

    public function toString(): string
    {
        /** @psalm-suppress InvalidArgument */
        return Str::of(',')
            ->join($this->names->map(
                static fn($element) => $element->toString(),
            ))
            ->prepend('{'.$this->expansion->toString())
            ->append('}')
            ->toString();
    }
}
