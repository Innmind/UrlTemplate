<?php
declare(strict_types = 1);

namespace Innmind\UrlTemplate;

use Innmind\Url\{
    UrlInterface,
    Url,
};
use Innmind\Immutable\{
    MapInterface,
    SetInterface,
    Set,
    Str,
};

final class Template
{
    private $template;
    private $expressions;

    public function __construct(string $template)
    {
        $this->template = Str::of($template);
        $this->expressions = $this
            ->extractExpressions(
                Set::of('string'),
                $this->template
            )
            ->reduce(
                Set::of(Expression::class),
                static function(SetInterface $expressions, string $expression): SetInterface {
                    return $expressions->add(Expressions::of(Str::of($expression)));
                }
            );
    }

    public static function of(string $template): self
    {
        return new self($template);
    }

    /**
     * @return SetInterface<Expression>
     */
    public function expressions(): SetInterface
    {
        return $this->expressions;
    }

    /**
     * @param MapInterface<string, variable> $variables
     */
    public function expand(MapInterface $variables): UrlInterface
    {
        if (
            (string) $variables->keyType() !== 'string' ||
            (string) $variables->valueType() !== 'variable'
        ) {
            throw new \TypeError('Argument 1 must be of type MapInterface<string, variable>');
        }

        $url = $this->expressions->reduce(
            $this->template,
            function(Str $template, Expression $expression) use ($variables): Str {
                return $template->replace(
                    (string) $expression,
                    $expression->expand($variables)
                );
            }
        );

        return Url::fromString((string) $url);
    }

    public function __toString(): string
    {
        return (string) $this->template;
    }

    /**
     * Recursively find the expressions as Str::capture doesnt capture all of
     * them at the same time
     */
    private function extractExpressions(
        SetInterface $expressions,
        Str $template
    ): SetInterface {
        $captured = $template->capture('~(\{[\+#\./;\?&]?[a-zA-Z0-9_]+(\*|:\d+)?(,[a-zA-Z0-9_]+(\*|:\d+)?)*\})~');

        if ($captured->size() === 0) {
            return $expressions;
        }

        return $this->extractExpressions(
            $expressions->add((string) $captured->current()),
            $template->replace((string) $captured->current(), '')
        );
    }
}
