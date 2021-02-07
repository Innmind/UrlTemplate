<?php
declare(strict_types = 1);

namespace Innmind\UrlTemplate;

use Innmind\UrlTemplate\Exception\{
    UrlDoesntMatchTemplate,
    ExtractionNotSupported,
    LogicException,
};
use Innmind\Url\Url;
use Innmind\Immutable\{
    Map,
    Sequence,
    Str,
};
use function Innmind\Immutable\assertMap;

final class Template
{
    private Str $template;
    /** @var Sequence<Expression> */
    private Sequence $expressions;

    private function __construct(string $template)
    {
        $this->template = Str::of($template);
        $this->expressions = $this
            ->extractExpressions(
                Sequence::of('string'),
                $this->template,
            )
            ->mapTo(
                Expression::class,
                static fn(string $expression) => Expressions::of(Str::of($expression)),
            );
    }

    public static function of(string $template): self
    {
        return new self($template);
    }

    /**
     * @param Map<string, scalar|array> $variables
     */
    public function expand(Map $variables): Url
    {
        assertMap('string', 'scalar|array', $variables, 1);

        $url = $this->expressions->reduce(
            $this->template,
            static function(Str $template, Expression $expression) use ($variables): Str {
                return $template->replace(
                    $expression->toString(),
                    $expression->expand($variables),
                );
            }
        );

        return Url::of($url->toString());
    }

    /**
     * @return Map<string, string>
     */
    public function extract(Url $url): Map
    {
        $regex = $this->regex();
        $url = Str::of($url->toString());

        if (!$url->matches($regex)) {
            throw new UrlDoesntMatchTemplate($url->toString());
        }

        /** @var Map<string, string> */
        return $url
            ->capture($regex)
            ->filter(static function($key): bool {
                return \is_string($key);
            })
            ->reduce(
                Map::of('string', 'string'),
                static function(Map $variables, $name, Str $variable): Map {
                    return ($variables)(
                        (string) $name,
                        \rawurldecode($variable->toString()),
                    );
                },
            );
    }

    public function matches(Url $url): bool
    {
        $regex = $this->regex();
        $url = Str::of($url->toString());

        return $url->matches($regex);
    }

    public function toString(): string
    {
        return $this->template->toString();
    }

    /**
     * Recursively find the expressions as Str::capture doesnt capture all of
     * them at the same time
     * @param Sequence<string> $expressions
     *
     * @return Sequence<string>
     */
    private function extractExpressions(
        Sequence $expressions,
        Str $template
    ): Sequence {
        $captured = $template->capture('~(\{[\+#\./;\?&]?[a-zA-Z0-9_]+(\*|:\d+)?(,[a-zA-Z0-9_]+(\*|:\d+)?)*\})~');

        if ($captured->size() === 0) {
            return $expressions;
        }

        return $this->extractExpressions(
            $expressions->add($captured->values()->first()->toString()),
            $template->replace($captured->values()->first()->toString(), ''),
        );
    }

    private function regex(): string
    {
        try {
            $i = 0;
            $j = 0;
            $template = $this
                ->expressions
                ->reduce(
                    $this->template->replace('~', '\~'),
                    static function(Str $template, Expression $expression) use (&$i): Str {
                        /**
                         * @psalm-suppress MixedOperand
                         * @psalm-suppress MixedAssignment
                         */
                        ++$i;

                        return $template->replace(
                            $expression->toString(),
                            "__innmind_expression_{$i}__",
                        );
                    },
                )
                ->pregQuote();
            $template = $this->expressions->reduce(
                $template,
                static function(Str $template, Expression $expression) use (&$j): Str {
                    /**
                     * @psalm-suppress MixedOperand
                     * @psalm-suppress MixedAssignment
                     */
                    ++$j;

                    return $template->replace(
                        "__innmind_expression_{$j}__",
                        $expression->regex(),
                    );
                },
            );
        } catch (LogicException $e) {
            throw new ExtractionNotSupported('', 0, $e);
        }

        return $template->prepend('~^')->append('$~')->toString();
    }
}
