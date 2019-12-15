<?php
declare(strict_types = 1);

namespace Innmind\UrlTemplate\Expression\Level3;

use Innmind\UrlTemplate\{
    Expression,
    Expression\Name,
    Expression\Level1,
};
use Innmind\Immutable\{
    MapInterface,
    Map,
    Sequence,
    StreamInterface,
    Stream,
    Str,
};

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
        $this->names = Sequence::of(...$names);
        $this->expressions = $this->names->reduce(
            new Map('string', Expression::class),
            static function(MapInterface $expressions, Name $name): MapInterface {
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
    public function expand(MapInterface $variables): string
    {
        return (string) $this
            ->expressions
            ->reduce(
                Stream::of('string'),
                function(StreamInterface $values, string $name, Expression $expression) use ($variables): StreamInterface {
                    $value = Str::of($expression->expand($variables));

                    if ($value->empty() && $this->keyOnlyWhenEmpty) {
                        return $values->add($name);
                    }

                    return $values->add("$name=$value");
                }
            )
            ->join($this->separator)
            ->prepend($this->lead);
    }

    public function regex(): string
    {
        return $this->regex ?? $this->regex = (string) $this
            ->names
            ->map(function(Name $name): string {
                return \sprintf(
                    '%s=%s%s',
                    $name,
                    $this->keyOnlyWhenEmpty ? '?' : '',
                    (new Level1($name))->regex()
                );
            })
            ->join('\\'.$this->separator)
            ->prepend('\\'.$this->lead);
    }

    public function __toString(): string
    {
        return $this->string ?? $this->string = (string) $this
            ->names
            ->join(',')
            ->prepend('{'.$this->lead)
            ->append('}');
    }
}
