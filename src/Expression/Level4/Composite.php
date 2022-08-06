<?php
declare(strict_types = 1);

namespace Innmind\UrlTemplate\Expression\Level4;

use Innmind\UrlTemplate\{
    Expression,
    Expression\Name,
    Expression\Expansion,
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
    private Expansion $expansion;
    /** @var Sequence<Expression> */
    private Sequence $expressions;

    /**
     * @param Sequence<Expression> $expressions
     */
    private function __construct(Expansion $expansion, Sequence $expressions)
    {
        $this->expansion = $expansion;
        $this->expressions = $expressions;
    }

    /**
     * @psalm-pure
     */
    public static function of(Str $string): Maybe
    {
        /** @var Maybe<Expression> */
        return Maybe::just($string)
            ->filter(Expansion::matchesLevel4(...))
            ->map(Expansion::simple->clean(...))
            ->map(static fn($string) => $string->split(','))
            ->flatMap(
                static fn($expressions) => $expressions
                    ->first()
                    ->map(static fn($first) => $first->prepend('{')->append('}'))
                    ->flatMap(Expressions::of(...))
                    ->flatMap(
                        static fn($first) => self::parse($first, $expressions->drop(1))
                            ->map(static fn($expressions) => new self(
                                $first->expansion(),
                                $expressions,
                            )),
                    ),
            );
    }

    public function expansion(): Expansion
    {
        return $this->expansion;
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
                    if ($this->removeLead()) {
                        return Str::of($value)->drop(1)->toString();
                    }

                    return $value;
                }),
            );

        return Str::of($this->expansion()->separator())->join($expanded)->toString();
    }

    public function regex(): string
    {
        $remaining = $this
            ->expressions
            ->drop(1)
            ->map(function(Expression $expression): string {
                if ($this->removeLead()) {
                    return Str::of($expression->regex())->drop(2)->toString();
                }

                return $expression->regex();
            });

        return Str::of($this->expansion()->separatorRegex())
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

    /**
     * @psalm-pure
     *
     * @param Sequence<Str> $expressions
     *
     * @return Maybe<Sequence<Expression>>
     */
    private static function parse(Expression $first, Sequence $expressions): Maybe
    {
        /** @var Maybe<Sequence<Expression>> */
        return Maybe::all(
            Maybe::just($first),
            ...$expressions
                ->map(static fn($expression) => $expression->prepend($first->expansion()->continuation()->toString()))
                ->map(static fn($expression) => $expression->prepend('{')->append('}'))
                ->map(Expressions::of(...))
                ->toList(),
        )
            ->map(Sequence::of(...));
    }

    private function removeLead(): bool
    {
        return $this->expansion() === Expansion::fragment;
    }
}
