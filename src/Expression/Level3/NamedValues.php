<?php
declare(strict_types = 1);

namespace Innmind\UrlTemplate\Expression\Level3;

use Innmind\UrlTemplate\{
    Expression,
    Expression\Name,
    Expression\Level1,
};
use Innmind\Immutable\{
    Map,
    Sequence,
    Str,
};

/**
 * @psalm-immutable
 */
final class NamedValues implements Expression
{
    private string $lead;
    private string $separator;
    /** @var Sequence<Name> */
    private Sequence $names;
    /** @var Map<string, Expression> */
    private Map $expressions;
    private bool $keyOnlyWhenEmpty = false;

    /**
     * @param Sequence<Name> $names
     */
    public function __construct(string $lead, string $separator, Sequence $names)
    {
        $this->lead = $lead;
        $this->separator = $separator;
        $this->names = $names;
        /** @var Map<string, Expression> */
        $this->expressions = Map::of(
            ...$this
                ->names
                ->map(static fn($name) => [
                    $name->toString(),
                    new Level1($name),
                ])
                ->toList(),
        );
    }

    /**
     * @psalm-pure
     */
    public static function of(Str $string): Expression
    {
        throw new \LogicException('should not be used directly');
    }

    /**
     * @psalm-pure
     *
     * @param Sequence<Name> $names
     */
    public static function keyOnlyWhenEmpty(string $lead, string $separator, Sequence $names): self
    {
        $self = new self($lead, $separator, $names);
        $self->keyOnlyWhenEmpty = true;

        return $self;
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

        return Str::of($this->separator)
            ->join($expanded)
            ->prepend($this->lead)
            ->toString();
    }

    public function regex(): string
    {
        return Str::of('\\'.$this->separator)
            ->join($this->names->map(
                fn($name) => \sprintf(
                    '%s=%s%s',
                    $name->toString(),
                    $this->keyOnlyWhenEmpty ? '?' : '',
                    (new Level1($name))->regex(),
                ),
            ))
            ->prepend('\\'.$this->lead)
            ->toString();
    }

    public function toString(): string
    {
        return Str::of(',')
            ->join($this->names->map(
                static fn($element) => $element->toString(),
            ))
            ->prepend('{'.$this->lead)
            ->append('}')
            ->toString();
    }
}
