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
     * @param Map<non-empty-string, string|list<string>|list<array{string, string}>> $variables
     */
    private function __construct(
        private Str $template,
        private Sequence $expressions,
        private Map $variables,
    ) {
    }

    /**
     * @psalm-pure
     * @internal
     *
     * @param Sequence<Expression> $expressions
     */
    public static function of(Str $template, Sequence $expressions): self
    {
        return new self($template, $expressions, Map::of());
    }

    /**
     * @param non-empty-string $name Todo use literal strings
     * @param string|list<string>|list<array{string, string}> $value
     */
    public function with(string $name, string|array $value): self
    {
        return new self(
            $this->template,
            $this->expressions,
            ($this->variables)($name, $value),
        );
    }

    public function expand(): Url
    {
        /** @var Map<non-empty-string, string> */
        $values = $this->variables->filter(
            static fn($_, $value) => \is_string($value),
        );
        /** @var Map<non-empty-string, list<string>> */
        $lists = $this->variables->filter(
            static fn($_, $value) => \is_array($value) && \array_all(
                $value,
                static fn($value) => \is_string($value),
            ),
        );
        /** @var Map<non-empty-string, list<array{Name, string}>> */
        $keys = $this
            ->variables
            ->filter(static fn($_, $value) => \is_array($value) && \array_all(
                $value,
                static fn($value) => \is_array($value),
            ))
            ->map(static fn($_, $keys) => \array_map(
                static fn($pair) => [Name::of($pair[0]), $pair[1]],
                $keys,
            ));

        $url = $this->expressions->reduce(
            $this->template,
            static fn(Str $template, $expression) => $template->replace(
                $expression->toString(),
                $expression->expand($values, $lists, $keys),
            ),
        );

        return Url::of($url->toString());
    }
}
