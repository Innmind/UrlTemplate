<?php
declare(strict_types = 1);

namespace Innmind\UrlTemplate\Expression\Level4;

use Innmind\UrlTemplate\{
    Expression,
    Expression\Name,
    Expression\Level1,
    Expression\Level3,
    Expression\Level4,
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

final class Query implements Expression
{
    private Name $name;
    private ?int $limit = null;
    private bool $explode = false;
    private Expression $expression;
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
        if ($string->matches('~^\{\?[a-zA-Z0-9_]+\}$~')) {
            return new self(new Name($string->trim('{?}')->toString()));
        }

        if ($string->matches('~^\{\?[a-zA-Z0-9_]+\*\}$~')) {
            return self::explode(new Name($string->trim('{?*}')->toString()));
        }

        if ($string->matches('~^\{\?[a-zA-Z0-9_]+:\d+\}$~')) {
            $string = $string->trim('{?}');
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
            '',
            $this,
            QueryContinuation::of($pattern->prepend('{&')->append('}'))
        );
    }

    /**
     * @param Map<string, variable> $variables
     */
    public function expand(Map $variables): string
    {
        if (!$variables->contains((string) $this->name)) {
            return '';
        }

        $variable = $variables->get((string) $this->name);

        if (\is_array($variable)) {
            return $this->expandList($variables, ...$variable);
        }

        if ($this->explode) {
            return $this->expandList($variables, $variable);
        }

        $value = Str::of($this->expression->expand($variables));

        if ($this->mustLimit()) {
            return "?{$this->name}={$value->substring(0, $this->limit)->toString()}";
        }

        return "?{$this->name}={$value->toString()}";
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

        return $this->regex = sprintf(
            '\?%s=%s',
            $this->name,
            $regex
        );
    }

    public function __toString(): string
    {
        if (\is_string($this->string)) {
            return $this->string;
        }

        if ($this->mustLimit()) {
            return $this->string = "{?{$this->name}:{$this->limit}}";
        }

        if ($this->explode) {
            return $this->string = "{?{$this->name}*}";
        }

        return $this->string = "{?{$this->name}}";
    }

    private function mustLimit(): bool
    {
        return \is_int($this->limit);
    }

    private function expandList(Map $variables, ...$elements): string
    {
        if ($this->explode) {
            return $this->explodeList($variables, $elements);
        }

        $elements = Sequence::mixed(...$elements)
            ->reduce(
                Sequence::mixed(),
                static function(Sequence $values, $element): Sequence {
                    if (\is_array($element)) {
                        [$name, $element] = $element;

                        return $values->add($name)->add($element);
                    }

                    return $values->add($element);
                }
            )
            ->map(function($element) use ($variables): string {
                return $this->expression->expand(
                    $variables->put(
                        (string) $this->name,
                        $element
                    )
                );
            })
            ->mapTo(
                'string',
                static fn($element) => (string) $element,
            );

        return join(',', $elements)
            ->prepend("?$this->name=")
            ->toString();
    }

    private function explodeList(Map $variables, array $elements): string
    {
        $elements = Sequence::mixed(...$elements)
            ->map(function($element) use ($variables): string {
                $name = $this->name;

                if (\is_array($element)) {
                    [$name, $element] = $element;
                    $name = new Name($name);
                }

                $value = (new Level3\Query($name))->expand(
                    $variables->put((string) $name, $element)
                );

                return Str::of($value)->substring(1)->toString();
            })
            ->mapTo(
                'string',
                static fn($element) => (string) $element,
            );

        return join('&', $elements)
            ->prepend('?')
            ->toString();
    }
}
