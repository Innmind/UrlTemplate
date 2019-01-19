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
    MapInterface,
    Str,
    SequenceInterface,
    Sequence,
};

final class Query implements Expression
{
    private $name;
    private $limit;
    private $explode;
    private $expression;
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
        if ($string->matches('~^\{\?[a-zA-Z0-9_]+\}$~')) {
            return new self(new Name((string) $string->trim('{?}')));
        }

        if ($string->matches('~^\{\?[a-zA-Z0-9_]+\*\}$~')) {
            return self::explode(new Name((string) $string->trim('{?*}')));
        }

        if ($string->matches('~^\{\?[a-zA-Z0-9_]+:\d+\}$~')) {
            $string = $string->trim('{?}');
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
            '',
            $this,
            QueryContinuation::of($pattern->prepend('{&')->append('}'))
        );
    }

    /**
     * @param MapInterface<string, variable> $variables
     */
    public function expand(MapInterface $variables): string
    {
        if (!$variables->contains((string) $this->name)) {
            return '';
        }

        $variable = $variables->get((string) $this->name);

        if (is_array($variable)) {
            return $this->expandList($variables, ...$variable);
        }

        if ($this->explode) {
            return $this->expandList($variables, $variable);
        }

        $value = Str::of($this->expression->expand($variables));

        if ($this->mustLimit()) {
            return "?{$this->name}={$value->substring(0, $this->limit)}";
        }

        return "?{$this->name}=$value";
    }

    public function regex(): string
    {
        if (\is_string($this->regex)) {
            return $this->regex;
        }

        if ($this->explode) {
            throw new LogicException;
        }

        if (is_int($this->limit)) {
            // replace '*' match by the actual limit
            $regex = (string) Str::of($this->expression->regex())
                ->substring(0, -2)
                ->append("{{$this->limit}})");
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
        return is_int($this->limit);
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
                    if (is_array($element)) {
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
            ->join(',')
            ->prepend("?$this->name=");
    }

    private function explodeList(MapInterface $variables, array $elements): string
    {
        return (string) Sequence::of(...$elements)
            ->map(function($element) use ($variables): string {
                $name = $this->name;

                if (is_array($element)) {
                    [$name, $element] = $element;
                    $name = new Name($name);
                }

                $value = (new Level3\Query($name))->expand(
                    $variables->put((string) $name, $element)
                );

                return (string) Str::of($value)->substring(1);
            })
            ->join('&')
            ->prepend('?');
    }
}
