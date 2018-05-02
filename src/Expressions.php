<?php
declare(strict_types = 1);

namespace Innmind\UrlTemplate;

use Innmind\UrlTemplate\Exception\DomainException;
use Innmind\Immutable\{
    Stream,
    Str,
};

final class Expressions
{
    private static $expressions;

    public static function of(Str $string): Expression
    {
        foreach (self::expressions() as $expression) {
            try {
                return [$expression, 'of']($string);
            } catch (DomainException $e) {
                //pass
            }
        }

        throw new DomainException((string) $string);
    }

    /**
     * @return Stream<string>
     */
    private static function expressions(): Stream
    {
        return self::$expressions ?? self::$expressions = Stream::of(
            'string',
            Expression\Level4::class,
            Expression\Level4\Reserved::class,
            Expression\Level4\Fragment::class,
            Expression\Level4\Label::class,
            Expression\Level4\Path::class,
            Expression\Level4\Parameters::class,
            Expression\Level4\Query::class,
            Expression\Level4\QueryContinuation::class,
            Expression\Level4\Composite::class
        );
    }
}
