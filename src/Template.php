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
    Set,
    Str,
};
use function Innmind\Immutable\assertMap;

final class Template
{
    private Str $template;
    /** @var Set<Expression> */
    private Set $expressions;

    private function __construct(string $template)
    {
        $this->template = Str::of($template);
        $this->expressions = $this
            ->extractExpressions(
                Set::of('string'),
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
     * @return Set<Expression>
     */
    public function expressions(): Set
    {
        return $this->expressions;
    }

    /**
     * @param Map<string, scalar|array> $variables
     */
    public function expand(Map $variables): Url
    {
        assertMap('string', 'scalar|array', $variables, 1);

        $url = $this->expressions->reduce(
            $this->template,
            function(Str $template, Expression $expression) use ($variables): Str {
                return $template->replace(
                    (string) $expression,
                    $expression->expand($variables)
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

        return $url
            ->capture($regex)
            ->filter(static function($key): bool {
                return \is_string($key);
            })
            ->reduce(
                Map::of('string', 'string'),
                static function(Map $variables, string $name, Str $variable): Map {
                    return $variables->put(
                        $name,
                        \rawurldecode($variable->toString())
                    );
                }
            );
    }

    public function matches(Url $url): bool
    {
        $regex = $this->regex();
        $url = Str::of($url->toString());

        return $url->matches($regex);
    }

    public function __toString(): string
    {
        return $this->template->toString();
    }

    /**
     * Recursively find the expressions as Str::capture doesnt capture all of
     * them at the same time
     */
    private function extractExpressions(
        Set $expressions,
        Str $template
    ): Set {
        $captured = $template->capture('~(\{[\+#\./;\?&]?[a-zA-Z0-9_]+(\*|:\d+)?(,[a-zA-Z0-9_]+(\*|:\d+)?)*\})~');

        if ($captured->size() === 0) {
            return $expressions;
        }

        return $this->extractExpressions(
            $expressions->add($captured->values()->first()->toString()),
            $template->replace($captured->values()->first()->toString(), '')
        );
    }

    private function regex(): string
    {
        try {
            $template = $this->expressions->reduce(
                $this->template->replace('~', '\~'),
                static function(Str $template, Expression $expression): Str {
                    return $template->replace(
                        (string) $expression,
                        $expression->regex()
                    );
                }
            );
        } catch (LogicException $e) {
            throw new ExtractionNotSupported('', 0, $e);
        }

        return $template->prepend('~^')->append('$~')->toString();
    }
}
