<?php
declare(strict_types = 1);

namespace Innmind\UrlTemplate\Expression;

use Innmind\UrlTemplate\{
    Expression,
    UrlEncode,
    Exception\DomainException,
    Exception\OnlyScalarCanBeExpandedForExpression,
};
use Innmind\Immutable\{
    Map,
    Str,
};

final class Level1 implements Expression
{
    private Name $name;
    private UrlEncode $encode;
    private ?string $regex = null;
    private ?string $string = null;

    public function __construct(Name $name)
    {
        $this->name = $name;
        $this->encode = new UrlEncode;
    }

    public static function of(Str $string): Expression
    {
        if (!$string->matches('~^\{[a-zA-Z0-9_]+\}$~')) {
            throw new DomainException($string->toString());
        }

        return new self(new Name($string->trim('{}')->toString()));
    }

    public function expand(Map $variables): string
    {
        if (!$variables->contains($this->name->toString())) {
            return '';
        }

        $variable = $variables->get($this->name->toString());

        if (\is_array($variable)) {
            throw new OnlyScalarCanBeExpandedForExpression($this->name->toString());
        }

        return ($this->encode)((string) $variable);
    }

    public function regex(): string
    {
        return $this->regex ?? $this->regex = "(?<{$this->name->toString()}>[a-zA-Z0-9\%\-\.\_\~]*)";
    }

    public function toString(): string
    {
        return $this->string ?? $this->string = "{{$this->name->toString()}}";
    }
}
