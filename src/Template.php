<?php
declare(strict_types = 1);

namespace Innmind\UrlTemplate;

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
    /**
     * @param Sequence<Expression> $expressions
     */
    private function __construct(
        private Str $template,
        private Sequence $expressions,
    ) {
    }

    /**
     * @psalm-pure
     *
     * @param literal-string $template
     *
     * @throws \Exception
     */
    #[\NoDiscard]
    public static function of(string $template): self
    {
        return self::attempt($template)->unwrap();
    }

    /**
     * @psalm-pure
     *
     *  @return Attempt<self>
     */
    #[\NoDiscard]
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
    #[\NoDiscard]
    public static function maybe(string $template): Maybe
    {
        return self::attempt($template)->maybe();
    }

    #[\NoDiscard]
    public function expansion(): Expansion
    {
        return Expansion::of($this->template, $this->expressions);
    }

    /**
     * The attempt will fail when trying to match on a template containing an
     * explode expression.
     *
     * @return Attempt<Map<string, string>>
     */
    #[\NoDiscard]
    public function extract(Url $url): Attempt
    {
        /** @var Attempt<Map<string, string>> */
        return $this
            ->regex()
            ->map(Str::of($url->toString())->capture(...))
            ->map(
                static fn($captured) => $captured
                    ->filter(static fn($key) => \is_string($key))
                    ->map(static fn($_, $variable) => \rawurldecode($variable->toString())),
            );
    }

    /**
     * The attempt will fail when trying to match on a template containing an
     * explode expression.
     *
     * @return Attempt<bool>
     */
    #[\NoDiscard]
    public function matches(Url $url): Attempt
    {
        return $this->regex()->map(
            Str::of($url->toString())->matches(...),
        );
    }

    #[\NoDiscard]
    public function toString(): string
    {
        return $this->template->toString();
    }

    /**
     * @return Attempt<string>
     */
    private function regex(): Attempt
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

        return $this
            ->expressions
            ->sink($template)
            ->attempt(
                static fn($template, $expression) => $expression
                    ->regex()
                    ->map(static fn($regex) => $template->replace(
                        \sprintf(
                            '__innmind_expression_%s__',
                            \spl_object_hash($expression),
                        ),
                        $regex,
                    )),
            )
            ->map(static fn($regex) => $regex->prepend('~^')->append('$~'))
            ->map(static fn($regex) => $regex->toString());
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
