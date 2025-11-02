<?php
declare(strict_types = 1);

namespace Innmind\UrlTemplate;

use Innmind\UrlTemplate\Exception\{
    ExplodeExpressionCantBeMatched,
    DomainException,
};
use Innmind\Url\Url;
use Innmind\Immutable\{
    Map,
    Sequence,
    Str,
    Maybe,
    Attempt,
};

/**
 * @psalm-immutable
 */
final class Template
{
    private Str $template;
    /** @var Sequence<Expression> */
    private Sequence $expressions;

    /**
     * @param Sequence<Expression> $expressions
     */
    private function __construct(Str $template, Sequence $expressions)
    {
        $this->template = $template;
        $this->expressions = $expressions;
    }

    /**
     * @psalm-pure
     *
     * @param literal-string $template
     *
     * @throws DomainException
     */
    public static function of(string $template): self
    {
        return self::attempt($template)->unwrap();
    }

    /**
     * @psalm-pure
     *
     *  @return Attempt<self>
     */
    public static function attempt(string $template): Attempt
    {
        $template = Str::of($template);

        return self::parse($template)->map(
            static fn($expressions) => new self($template, $expressions),
        );
    }

    /**
     * @psalm-pure
     *
     *  @return Maybe<self>
     */
    public static function maybe(string $template): Maybe
    {
        return self::attempt($template)->maybe();
    }

    /**
     * @param Map<non-empty-string, string|list<string>|list<array{string, string}>> $variables
     */
    public function expand(Map $variables): Url
    {
        $url = $this->expressions->reduce(
            $this->template,
            static fn(Str $template, $expression) => $template->replace(
                $expression->toString(),
                $expression->expand($variables),
            ),
        );

        return Url::of($url->toString());
    }

    /**
     * @throws ExplodeExpressionCantBeMatched
     *
     * @return Map<string, string>
     */
    public function extract(Url $url): Map
    {
        /** @var Map<string, string> */
        return Str::of($url->toString())
            ->capture($this->regex())
            ->filter(static fn($key) => \is_string($key))
            ->map(static fn($_, $variable) => \rawurldecode($variable->toString()));
    }

    /**
     * @throws ExplodeExpressionCantBeMatched
     */
    public function matches(Url $url): bool
    {
        return Str::of($url->toString())->matches($this->regex());
    }

    public function toString(): string
    {
        return $this->template->toString();
    }

    /**
     * @throws ExplodeExpressionCantBeMatched
     */
    private function regex(): string
    {
        $template = $this
            ->expressions
            ->reduce(
                $this->template->replace('~', '\~'),
                static fn(Str $template, $expression) => $template->replace(
                    $expression->toString(),
                    \sprintf(
                        '__innmind_expression_%s__',
                        \spl_object_hash($expression),
                    ),
                ),
            )
            ->pregQuote();
        $template = $this->expressions->reduce(
            $template,
            static fn(Str $template, $expression) => $template->replace(
                \sprintf(
                    '__innmind_expression_%s__',
                    \spl_object_hash($expression),
                ),
                $expression->regex(),
            ),
        );

        return $template->prepend('~^')->append('$~')->toString();
    }

    /**
     * @psalm-pure
     *
     * Recursively find the expressions as Str::capture doesnt capture all of
     * them at the same time
     *
     * @return Attempt<Sequence<Expression>>
     */
    private static function parse(Str $template): Attempt
    {
        /** @var Sequence<Str> */
        $expressions = Sequence::of();

        do {
            $captured = $template->capture('~(\{[\+#\./;\?&]?[a-zA-Z0-9_]+(\*|:\d+)?(,[a-zA-Z0-9_]+(\*|:\d+)?)*\})~');

            [$expressions, $template] = $captured
                ->get(0)
                ->match(
                    static fn($value) => [
                        ($expressions)($value),
                        $template->replace($value->toString(), ''),
                    ],
                    static fn() => [$expressions, $template],
                );
        } while (!$captured->empty());

        /** @var Sequence<Expression> */
        $parsed = Sequence::of();

        return $expressions
            ->map(Expressions::of(...))
            ->sink($parsed)
            ->attempt(static fn($expressions, $expression) => $expression->map($expressions));
    }
}
