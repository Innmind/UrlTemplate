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
    Map,
    Str,
    Sequence,
};
use function Innmind\Immutable\{
    join,
    unwrap,
};

final class Level4 implements Expression
{
    private Name $name;
    private Expression $expression;
    private ?int $limit = null;
    private bool $explode = false;
    private string $lead = '';
    private string $separator = ',';
    private ?string $regex = null;
    private ?string $string = null;

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
            return new self(new Name($string->trim('{}')->toString()));
        }

        if ($string->matches('~^\{[a-zA-Z0-9_]+\*\}$~')) {
            return self::explode(new Name($string->trim('{*}')->toString()));
        }

        if ($string->matches('~^\{[a-zA-Z0-9_]+:\d+\}$~')) {
            $string = $string->trim('{}');
            [$name, $limit] = unwrap($string->split(':'));

            return self::limit(
                new Name($name->toString()),
                (int) $limit->toString()
            );
        }

        throw new DomainException($string->toString());
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
    public function expand(Map $variables): string
    {
        if (!$variables->contains($this->name->toString())) {
            return '';
        }

        $variable = $variables->get($this->name->toString());

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
                    $this->name->toString(),
                    $value->toString()
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
            $regex = Str::of($this->expression->regex())
                ->substring(0, -2)
                ->append("{{$this->limit}})")
                ->toString();
        } else {
            $regex = $this->expression->regex();
        }

        return $this->regex = \sprintf(
            '%s%s',
            $this->lead ? '\\'.$this->lead : '',
            $regex
        );
    }

    public function toString(): string
    {
        if (\is_string($this->string)) {
            return $this->string;
        }

        if ($this->mustLimit()) {
            return $this->string = "{{$this->lead}{$this->name->toString()}:{$this->limit}}";
        }

        if ($this->explode) {
            return $this->string = "{{$this->lead}{$this->name->toString()}*}";
        }

        return $this->string = "{{$this->lead}{$this->name->toString()}}";
    }

    private function mustLimit(): bool
    {
        return \is_int($this->limit);
    }

    /**
     * @param Map<string, scalar|array> $variables
     */
    private function expandList(Map $variables, ...$elements): string
    {
        if ($this->explode) {
            return $this->explodeList($variables, $elements);
        }

        $elements = Sequence::mixed(...$elements)
            ->reduce(
                Sequence::strings(),
                static function(Sequence $values, $element): Sequence {
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
                        $this->name->toString(),
                        $element
                    )
                );
            });

        return join($this->separator, $elements)
            ->prepend($this->lead)
            ->toString();
    }

    /**
     * @param Map<string, scalar|array> $variables
     */
    private function explodeList(Map $variables, array $elements): string
    {
        $elements = Sequence::mixed(...$elements)
            ->map(function($element) use ($variables): string {
                if (\is_array($element)) {
                    [$name, $element] = $element;
                }

                $value = $this->expression->expand(
                    $variables->put(
                        $this->name->toString(),
                        $element
                    )
                );

                if (isset($name)) {
                    $value = \sprintf(
                        '%s=%s',
                        (new Name($name))->toString(),
                        $value
                    );
                }

                return $value;
            })
            ->mapTo(
                'string',
                static fn($element) => (string) $element,
            );

        return join($this->separator, $elements)
            ->prepend($this->lead)
            ->toString();
    }
}
