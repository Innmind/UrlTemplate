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
use function Innmind\Immutable\join;

final class NamedValues implements Expression
{
    private string $lead;
    private string $separator;
    /** @var Sequence<Name> */
    private Sequence $names;
    /** @var Map<string, Expression> */
    private Map $expressions;
    private bool $keyOnlyWhenEmpty = false;
    private ?string $regex = null;
    private ?string $string = null;

    public function __construct(string $lead, string $separator, Name ...$names)
    {
        $this->lead = $lead;
        $this->separator = $separator;
        /** @var Sequence<Name> */
        $this->names = Sequence::of(Name::class, ...$names);
        /** @var Map<string, Expression> */
        $this->expressions = $this->names->toMapOf(
            'string',
            Expression::class,
            static fn(Name $name): \Generator => yield $name->toString() => new Level1($name),
        );
    }

    public static function of(Str $string): Expression
    {
        throw new \LogicException('should not be used directly');
    }

    public static function keyOnlyWhenEmpty(string $lead, string $separator, Name ...$names): self
    {
        $self = new self($lead, $separator, ...$names);
        $self->keyOnlyWhenEmpty = true;

        return $self;
    }

    public function expand(Map $variables): string
    {
        /** @var Sequence<string> */
        $expanded = $this->expressions->reduce(
            Sequence::of('string'),
            function(Sequence $expanded, string $name, Expression $expression) use ($variables): Sequence {
                $value = Str::of($expression->expand($variables));

                if ($value->empty() && $this->keyOnlyWhenEmpty) {
                    return ($expanded)($name);
                }

                return ($expanded)("$name={$value->toString()}");
            },
        );

        return join($this->separator, $expanded)
            ->prepend($this->lead)
            ->toString();
    }

    public function regex(): string
    {
        return $this->regex ?? $this->regex = join(
            '\\'.$this->separator,
            $this->names->mapTo(
                'string',
                fn($name) => \sprintf(
                    '%s=%s%s',
                    $name->toString(),
                    $this->keyOnlyWhenEmpty ? '?' : '',
                    (new Level1($name))->regex(),
                ),
            ),
        )
            ->prepend('\\'.$this->lead)
            ->toString();
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
            ->prepend('{'.$this->lead)
            ->append('}')
            ->toString();
    }
}
