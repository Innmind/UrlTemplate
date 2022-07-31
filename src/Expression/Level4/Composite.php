<?php
declare(strict_types = 1);

namespace Innmind\UrlTemplate\Expression\Level4;

use Innmind\UrlTemplate\{
    Expression,
    Expression\Name,
    Expressions,
};
use Innmind\Immutable\{
    Map,
    Sequence,
    Str,
    Maybe,
};

/**
 * @psalm-immutable
 */
final class Composite implements Expression
{
    private string $separator;
    private string $type;
    /** @var Sequence<Expression> */
    private Sequence $expressions;
    private bool $removeLead = false;

    public function __construct(
        string $separator,
        Expression $level4,
        Expression ...$expressions,
    ) {
        $this->separator = $separator;
        $this->type = \get_class($level4);
        $this->expressions = Sequence::of($level4, ...$expressions);
    }

    /**
     * @psalm-pure
     */
    public static function removeLead(
        string $separator,
        Expression $level4,
        Expression ...$expressions,
    ): self {
        $self = new self($separator, $level4, ...$expressions);
        $self->removeLead = true;

        return $self;
    }

    /**
     * @psalm-pure
     */
    public static function of(Str $string): Maybe
    {
        /**
         * @psalm-suppress MixedInferredReturnType
         * @psalm-suppress MixedReturnStatement
         * @psalm-suppress UndefinedInterfaceMethod
         */
        return Maybe::just($string)
            ->filter(static fn($string) => $string->matches('~^\{[\+#\./;\?&]?[a-zA-Z0-9_]+(\*|:\d*)?(,[a-zA-Z0-9_]+(\*|:\d*)?)+\}$~'))
            ->map(static fn($string) => $string->trim('{}')->split(','))
            ->flatMap(
                static fn($pieces) => $pieces
                    ->first()
                    ->map(static fn($first) => $first->prepend('{')->append('}'))
                    ->map(Expressions::of(...))
                    ->map(
                        static fn($first) => $pieces->drop(1)->reduce(
                            $first,
                            static fn(Expression $level4, $expression): Expression => $level4->add($expression),
                        ),
                    ),
            );
    }

    public function expand(Map $variables): string
    {
        $expanded = $this->expressions->map(
            static fn($expression) => $expression->expand($variables),
        );

        //potentially remove the lead characters from the expressions except for
        //the first one, needed for the fragment composite

        $expanded = $expanded
            ->take(1)
            ->append(
                $expanded->drop(1)->map(function(string $value): string {
                    if ($this->removeLead) {
                        return Str::of($value)->drop(1)->toString();
                    }

                    return $value;
                }),
            );

        return Str::of($this->separator)->join($expanded)->toString();
    }

    public function regex(): string
    {
        $remaining = $this
            ->expressions
            ->drop(1)
            ->map(function(Expression $expression): string {
                if ($this->removeLead) {
                    return Str::of($expression->regex())->drop(2)->toString();
                }

                return $expression->regex();
            });

        return Str::of($this->separator ? '\\'.$this->separator : '')
            ->join(
                $this
                    ->expressions
                    ->take(1)
                    ->map(static fn($expression) => $expression->regex())
                    ->append($remaining),
            )
            ->toString();
    }

    public function toString(): string
    {
        $expressions = $this->expressions->map(
            static fn($expression) => Str::of($expression->toString())->trim('{}'),
        );

        //only keep the lead character for the first expression and remove it
        //for the following ones

        $expressions = $expressions
            ->take(1)
            ->append(
                $expressions
                    ->drop(1)
                    ->map(static function(Str $expression): Str {
                        return $expression->leftTrim('+#/.;?&');
                    }),
            )
            ->map(static fn($element) => $element->toString());

        return Str::of(',')
            ->join($expressions)
            ->prepend('{')
            ->append('}')
            ->toString();
    }
}
