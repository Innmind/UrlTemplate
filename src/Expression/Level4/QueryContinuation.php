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
};
use Innmind\Immutable\{
    MapInterface,
    Str,
    SequenceInterface,
    Sequence,
};

final class QueryContinuation implements Expression
{
    private $name;
    private $limit;
    private $explode;
    private $expression;

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
        if ($string->matches('~^\{\&[a-zA-Z0-9_]+\}$~')) {
            return new self(new Name((string) $string->trim('{&}')));
        }

        if ($string->matches('~^\{\&[a-zA-Z0-9_]+\*\}$~')) {
            return self::explode(new Name((string) $string->trim('{&*}')));
        }

        if ($string->matches('~^\{\&[a-zA-Z0-9_]+:\d+\}$~')) {
            $string = $string->trim('{&}');
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
            self::of($pattern->prepend('{&')->append('}'))
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
            return "&{$this->name}={$value->substring(0, $this->limit)}";
        }

        return "&{$this->name}=$value";
    }

    public function __toString(): string
    {
        if ($this->mustLimit()) {
            return "{&{$this->name}:{$this->limit}}";
        }

        if ($this->explode) {
            return "{&{$this->name}*}";
        }

        return "{&{$this->name}}";
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
            ->prepend("&$this->name=");
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

                return (new Level3\QueryContinuation($name))->expand(
                    $variables->put((string) $name, $element)
                );
            })
            ->join('');
    }
}
