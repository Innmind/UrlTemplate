<?php
declare(strict_types = 1);

namespace Innmind\UrlTemplate\Expression\Level4;

use Innmind\UrlTemplate\{
    Expression,
    Expression\Name,
    Expression\Level2,
    Exception\DomainException,
};
use Innmind\Immutable\{
    MapInterface,
    Str,
    Sequence,
};

final class Reserved implements Expression
{
    private $name;
    private $limit;
    private $explode;
    private $expression;

    public function __construct(Name $name)
    {
        $this->name = $name;
        $this->expression = new Level2\Reserved($name);

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
            return (string) $value->substring(0, $this->limit);
        }

        return (string) $value;
    }

    public function __toString(): string
    {
        if ($this->mustLimit()) {
            return "{+{$this->name}:{$this->limit}}";
        }

        if ($this->explode) {
            return "{+{$this->name}*}";
        }

        return "{+{$this->name}}";
    }

    private function mustLimit(): bool
    {
        return is_int($this->limit);
    }

    private function expandList(MapInterface $variables, ...$elements): string
    {
        return (string) Sequence::of(...$elements)
            ->map(function($element) use ($variables): string {
                if (is_array($element)) {
                    [$name, $element] = $element;
                }

                $value = $this->expression->expand(
                    $variables->put(
                        (string) $this->name,
                        $element
                    )
                );

                if (isset($name)) {
                    $value = sprintf(
                        '%s=%s',
                        new Name($name),
                        $value
                    );
                }

                return $value;
            })
            ->join(',');
    }
}
