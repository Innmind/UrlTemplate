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
    private Sequence $names;
    private Map $expressions;
    private bool $keyOnlyWhenEmpty = false;
    private ?string $regex = null;
    private ?string $string = null;

    public function __construct(string $lead, string $separator, Name ...$names)
    {
        $this->lead = $lead;
        $this->separator = $separator;
        $this->names = Sequence::mixed(...$names);
        $this->expressions = $this->names->reduce(
            Map::of('string', Expression::class),
            static function(Map $expressions, Name $name): Map {
                return $expressions->put(
                    (string) $name,
                    new Level1($name)
                );
            });
    }

    /**
     * {@inheritdoc}
     */
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

    /**
     * {@inheritdoc}
     */
    public function expand(Map $variables): string
    {
        $elements = $this
            ->expressions
            ->reduce(
                Sequence::of('string'),
                function(Sequence $values, string $name, Expression $expression) use ($variables): Sequence {
                    $value = Str::of($expression->expand($variables));

                    if ($value->empty() && $this->keyOnlyWhenEmpty) {
                        return $values->add($name);
                    }

                    return $values->add("$name={$value->toString()}");
                }
            );

        return join($this->separator, $elements)
            ->prepend($this->lead)
            ->toString();
    }

    public function regex(): string
    {
        return $this->regex ?? $this->regex = join(
            '\\'.$this->separator,
            $this
                ->names
                ->map(function(Name $name): string {
                    return \sprintf(
                        '%s=%s%s',
                        $name,
                        $this->keyOnlyWhenEmpty ? '?' : '',
                        (new Level1($name))->regex()
                    );
                })
                ->toSequenceOf('string'),
        )
            ->prepend('\\'.$this->lead)
            ->toString();
    }

    public function __toString(): string
    {
        return $this->string ?? $this->string = join(
            ',',
            $this
                ->names
                ->toSequenceOf(
                    'string',
                    static fn($element): \Generator => yield (string) $element,
                ),
        )
            ->prepend('{'.$this->lead)
            ->append('}')
            ->toString();
    }
}
