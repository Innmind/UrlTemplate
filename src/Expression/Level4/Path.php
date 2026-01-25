<?php
declare(strict_types = 1);

namespace Innmind\UrlTemplate\Expression\Level4;

use Innmind\UrlTemplate\{
    Expression,
    Expression\Name,
    Expression\Expansion,
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
final class Path implements Expression
{
    private function __construct(
        private Level4 $expression,
    ) {
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
            static fn(Name $name) => new self(
                Level4::named($name)->withExpansion(Expansion::path),
            ),
            self::explode(...),
            self::limit(...),
            Expansion::path,
        );
    }

    /**
     * @psalm-pure
     *
     * @param int<1, max> $limit
     */
    public static function limit(Name $name, int $limit): self
    {
        return new self(
            Level4::limit($name, $limit)->withExpansion(Expansion::path),
        );
    }

    /**
     * @psalm-pure
     */
    public static function explode(Name $name): self
    {
        return new self(
            Level4::explode($name)->withExpansion(Expansion::path),
        );
    }

    #[\Override]
    public function expansion(): Expansion
    {
        return Expansion::path;
    }

    #[\Override]
    public function expand(Map $values, Map $lists, Map $keys): string
    {
        return $this->expression->expand($values, $lists, $keys);
    }

    #[\Override]
    public function regex(): Attempt
    {
        return $this->expression->regex();
    }

    #[\Override]
    public function toString(): string
    {
        return $this->expression->toString();
    }
}
