<?php
declare(strict_types = 1);

namespace Innmind\UrlTemplate\Expression\Level4;

use Innmind\UrlTemplate\{
    Expression,
    Expression\Expansion,
    Expressions,
};
use Innmind\Immutable\{
    Map,
    Sequence,
    Str,
    Maybe,
    Attempt,
};

/**
 * @psalm-immutable
 * @internal
 */
final class Composite implements Expression
{
    /**
     * @param Sequence<Expression> $expressions
     */
    private function __construct(
        private Expansion $expansion,
        private Sequence $expressions,
    ) {
    }

    /**
     * @psalm-pure
     *
     * @return Attempt<self>
     */
    public static function of(Str $string): Attempt
    {
        return Maybe::just($string)
            ->filter(Expansion::matchesLevel4(...))
            ->map(Expansion::simple->clean(...))
            ->map(static fn($string) => $string->split(','))
            ->attempt(static fn() => new \LogicException('Cannot parse level 4 composite'))
            ->flatMap(
                static fn($expressions) => $expressions
                    ->first()
                    ->map(static fn($first) => $first->prepend('{')->append('}'))
                    ->attempt(static fn() => new \LogicException('First expression not found'))
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

    #[\Override]
    public function expansion(): Expansion
    {
        return $this->expansion;
    }

    #[\Override]
    public function expand(Map $values, Map $lists, Map $keys): string
    {
        $expanded = $this->expressions->map(
            static fn($expression) => $expression->expand($values, $lists, $keys),
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

    #[\Override]
    public function regex(): Attempt
    {
        $remaining = $this
            ->expressions
            ->drop(1)
            ->map(function($expression) {
                if ($this->removeLead()) {
                    return $expression
                        ->regex()
                        ->map(Str::of(...))
                        ->map(static fn($regex) => $regex->drop(2)->toString());
                }

                return $expression->regex();
            });

        return $this
            ->expressions
            ->take(1)
            ->map(static fn($expression) => $expression->regex())
            ->append($remaining)
            ->sink(Sequence::strings())
            ->attempt(static fn($regexes, $regex) => $regex->map($regexes))
            ->map(
                fn($regexes) => Str::of($this->expansion()->separatorRegex())
                    ->join($regexes)
                    ->toString(),
            );
    }

    #[\Override]
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
     * @return Attempt<Sequence<Expression>>
     */
    private static function parse(Expression $first, Sequence $expressions): Attempt
    {
        return $expressions
            ->map(static fn($expression) => $expression->prepend(
                $first->expansion()->continuation()->toString(),
            ))
            ->map(static fn($expression) => $expression->prepend('{')->append('}'))
            ->map(Expressions::of(...))
            ->sink(Sequence::of($first))
            ->attempt(static fn($expressions, $expression) => $expression->map($expressions));
    }

    private function removeLead(): bool
    {
        return $this->expansion() === Expansion::fragment;
    }
}
