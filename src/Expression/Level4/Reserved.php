<?php
declare(strict_types = 1);

namespace Innmind\UrlTemplate\Expression\Level4;

use Innmind\UrlTemplate\{
    Expression,
    Expression\Name,
    Expression\Expansion,
    Expression\Level2,
    Expression\Level4,
};
use Innmind\Immutable\{
    Map,
    Str,
    Attempt,
};

/**
 * @psalm-immutable
 * @internal
 */
final class Reserved implements Expression
{
    private Name $name;
    /** @var ?int<1, max> */
    private ?int $limit = null;
    private bool $explode = false;
    private Level4 $expression;

    private function __construct(Name $name)
    {
        $this->name = $name;
        $this->expression = Level4::named($name)->withExpression(
            Level2\Reserved::named(...),
        );
    }

    /**
     * @psalm-pure
     *
     * @return Attempt<self>
     */
    public static function of(Str $string): Attempt
    {
        return Parse::of(
            $string,
            static fn(Name $name) => new self($name),
            self::explode(...),
            self::limit(...),
            Expansion::reserved,
        );
    }

    /**
     * @psalm-pure
     *
     * @param int<1, max> $limit
     */
    public static function limit(Name $name, int $limit): self
    {
        $self = new self($name);
        $self->limit = $limit;
        $self->expression = Level4::limit($name, $limit)->withExpression(
            Level2\Reserved::named(...),
        );

        return $self;
    }

    /**
     * @psalm-pure
     */
    public static function explode(Name $name): self
    {
        $self = new self($name);
        $self->explode = true;
        $self->expression = Level4::explode($name)->withExpression(
            Level2\Reserved::named(...),
        );

        return $self;
    }

    #[\Override]
    public function expansion(): Expansion
    {
        return Expansion::reserved;
    }

    #[\Override]
    public function expand(Map $values, Map $lists, Map $keys): string
    {
        return $this->expression->expand($values, $lists, $keys);
    }

    #[\Override]
    public function regex(): Attempt
    {
        if ($this->explode) {
            return Attempt::error(new \LogicException('Explode expression cant be matched'));
        }

        if (\is_int($this->limit)) {
            return Attempt::result("(?<{$this->name->toString()}>[a-zA-Z0-9\%:/\?#\[\]@!\$&'\(\)\*\+,;=\-\.\_\~]{{$this->limit}})");
        }

        return $this->expression->regex();
    }

    #[\Override]
    public function toString(): string
    {
        if (\is_int($this->limit)) {
            return "{+{$this->name->toString()}:{$this->limit}}";
        }

        if ($this->explode) {
            return "{+{$this->name->toString()}*}";
        }

        return "{+{$this->name->toString()}}";
    }
}
