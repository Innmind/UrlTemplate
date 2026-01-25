<?php
declare(strict_types = 1);

namespace Innmind\UrlTemplate;

use Innmind\UrlTemplate\Expression\Name;
use Innmind\Url\Url;
use Innmind\Immutable\{
    Sequence,
    Str,
    Map,
};

/**
 * @psalm-immutable
 */
final class Expansion
{
    /**
     * @param Sequence<Expression> $expressions
     * @param Map<non-empty-string, string> $values
     * @param Map<non-empty-string, list<string>> $lists
     * @param Map<non-empty-string, list<array{Name, string}>> $keys
     */
    private function __construct(
        private Str $template,
        private Sequence $expressions,
        private Map $values,
        private Map $lists,
        private Map $keys,
    ) {
    }

    /**
     * @psalm-pure
     * @internal
     *
     * @param Sequence<Expression> $expressions
     */
    #[\NoDiscard]
    public static function of(Str $template, Sequence $expressions): self
    {
        return new self(
            $template,
            $expressions,
            Map::of(),
            Map::of(),
            Map::of(),
        );
    }

    /**
     * @no-named-arguments
     *
     * @param non-empty-string $name
     */
    #[\NoDiscard]
    public function with(string $name, string ...$values): self
    {
        if (\count($values) === 1) {
            return new self(
                $this->template,
                $this->expressions,
                ($this->values)($name, $values[0]),
                $this->lists->remove($name),
                $this->keys->remove($name),
            );
        }

        return new self(
            $this->template,
            $this->expressions,
            $this->values->remove($name),
            ($this->lists)($name, $values),
            $this->keys->remove($name),
        );
    }

    /**
     * @no-named-arguments
     *
     * @param non-empty-string $name
     * @param array{string, string} ...$keys
     */
    #[\NoDiscard]
    public function withKeys(string $name, array ...$keys): self
    {
        return new self(
            $this->template,
            $this->expressions,
            $this->values->remove($name),
            $this->lists->remove($name),
            ($this->keys)($name, \array_map(
                static fn($pair) => [Name::of($pair[0]), $pair[1]],
                $keys,
            )),
        );
    }

    #[\NoDiscard]
    public function expand(): Url
    {
        $url = $this->expressions->reduce(
            $this->template,
            fn(Str $template, $expression) => $template->replace(
                $expression->toString(),
                $expression->expand(
                    $this->values,
                    $this->lists,
                    $this->keys,
                ),
            ),
        );

        return Url::of($url->toString());
    }
}
