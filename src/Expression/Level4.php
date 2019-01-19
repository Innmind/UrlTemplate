<?php
declare(strict_types = 1);

namespace Innmind\UrlTemplate\Expression;

use Innmind\UrlTemplate\{
    Expression,
    Expression\Level4\Composite,
    Exception\DomainException,
    Exception\LogicException,
};
use Innmind\Immutable\{
    MapInterface,
    Str,
    SequenceInterface,
    Sequence,
};

final class Level4 implements Expression
{
    private $name;
    private $expression;
    private $limit;
    private $explode;
    private $lead = '';
    private $separator = ',';
    private $regex;
    private $string;

    public function __construct(Name $name)
    {
        $this->name = $name;
        $this->expression = new Level1($name);
    }

    /**
     * {@inheritdoc}
     */
    public static function of(Str $string): Expression
    {
        if ($string->matches('~^\{[a-zA-Z0-9_]+\}$~')) {
            return new self(new Name((string) $string->trim('{}')));
        }

        if ($string->matches('~^\{[a-zA-Z0-9_]+\*\}$~')) {
            return self::explode(new Name((string) $string->trim('{*}')));
        }

        if ($string->matches('~^\{[a-zA-Z0-9_]+:\d+\}$~')) {
            $string = $string->trim('{}');
            [$name, $limit] = $string->split(':');

            return self::limit(
                new Name((string) $name),
                (int) (string) $limit
            );
        }

        throw new DomainException((string) $string);
    }

    public static function limit(Name $name, int $limit): self
    {
        if ($limit < 0) {
            throw new DomainException;
        }

        $self = new self($name);
        $self->limit = $limit;

        return $self;
    }

    public static function explode(Name $name): self
    {
        $self = new self($name);
        $self->explode = true;

        return $self;
    }

    public function add(Str $pattern): Composite
    {
        return new Composite(
            ',',
            $this,
            self::of($pattern->prepend('{')->append('}'))
        );
    }

    public function withLead(string $lead): self
    {
        $self = clone $this;
        $self->lead = $lead;

        return $self;
    }

    public function withSeparator(string $separator): self
    {
        $self = clone $this;
        $self->separator = $separator;

        return $self;
    }

    /**
     * Not ideal technic but didn't find a better to reduce duplicated code
     * @internal
     */
    public function withExpression(string $expression): self
    {
        if (!(new \ReflectionClass($expression))->implementsInterface(Expression::class)) {
            throw new DomainException($expression);
        }

        $self = clone $this;
        $self->expression = new $expression($self->name);

        return $self;
    }

    /**
     * {@inheritdoc}
     */
    public function expand(MapInterface $variables): string
    {
        if (!$variables->contains((string) $this->name)) {
            return '';
        }

        $variable = $variables->get((string) $this->name);

        if (\is_array($variable)) {
            return $this->expandList($variables, ...$variable);
        }

        if ($this->explode) {
            return $this->explodeList($variables, [$variable]);
        }

        if ($this->mustLimit()) {
            $value = Str::of((string) $variable)->substring(0, $this->limit);
            $value = $this->expression->expand(
                $variables->put(
                    (string) $this->name,
                    (string) $value
                )
            );
        } else {
            $value = $this->expression->expand($variables);
        }

        return "{$this->lead}$value";
    }

    public function regex(): string
    {
        if (\is_string($this->regex)) {
            return $this->regex;
        }

        if ($this->explode) {
            throw new LogicException;
        }

        if ($this->mustLimit()) {
            // replace '*' match by the actual limit
            $regex = (string) Str::of($this->expression->regex())
                ->substring(0, -2)
                ->append("{{$this->limit}})");
        } else {
            $regex = $this->expression->regex();
        }

        return $this->regex = \sprintf(
            '%s%s',
            $this->lead ? '\\'.$this->lead : '',
            $regex
        );
    }

    public function __toString(): string
    {
        if (\is_string($this->string)) {
            return $this->string;
        }

        if ($this->mustLimit()) {
            return $this->string = "{{$this->lead}{$this->name}:{$this->limit}}";
        }

        if ($this->explode) {
            return $this->string = "{{$this->lead}{$this->name}*}";
        }

        return $this->string = "{{$this->lead}{$this->name}}";
    }

    private function mustLimit(): bool
    {
        return \is_int($this->limit);
    }

    private function expandList(MapInterface $variables, ...$elements): string
    {
        if ($this->explode) {
            return $this->explodeList($variables, $elements);
        }

        return (string) Sequence::of(...$elements)
            ->reduce(
                new Sequence,
                static function(SequenceInterface $values, $element): SequenceInterface {
                    if (\is_array($element)) {
                        [$name, $element] = $element;

                        return $values->add($name)->add($element);
                    }

                    return $values->add($element);
                }
            )
            ->map(function(string $element) use ($variables): string {
                return $this->expression->expand(
                    $variables->put(
                        (string) $this->name,
                        $element
                    )
                );
            })
            ->join($this->separator)
            ->prepend($this->lead);
    }

    private function explodeList(MapInterface $variables, array $elements): string
    {
        return (string) Sequence::of(...$elements)
            ->map(function($element) use ($variables): string {
                if (\is_array($element)) {
                    [$name, $element] = $element;
                }

                $value = $this->expression->expand(
                    $variables->put(
                        (string) $this->name,
                        $element
                    )
                );

                if (isset($name)) {
                    $value = \sprintf(
                        '%s=%s',
                        new Name($name),
                        $value
                    );
                }

                return $value;
            })
            ->join($this->separator)
            ->prepend($this->lead);
    }
}
