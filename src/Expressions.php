<?php
declare(strict_types = 1);

namespace Innmind\UrlTemplate;

use Innmind\UrlTemplate\Exception\DomainException;
use Innmind\Immutable\{
    Sequence,
    Str,
};

final class Expressions
{
    /** @var list<string> */
    private static ?array $expressions = null;

    public static function of(Str $string): Expression
    {
        foreach (self::expressions() as $expression) {
            try {
                /** @var Expression */
                return [$expression, 'of']($string);
            } catch (DomainException $e) {
                //pass
            }
        }

        throw new DomainException($string->toString());
    }

    /**
     * @return list<string>
     */
    private static function expressions(): array
    {
        return self::$expressions ?? self::$expressions = [
            Expression\Level4::class,
            Expression\Level4\Reserved::class,
            Expression\Level4\Fragment::class,
            Expression\Level4\Label::class,
            Expression\Level4\Path::class,
            Expression\Level4\Parameters::class,
            Expression\Level4\Query::class,
            Expression\Level4\QueryContinuation::class,
            Expression\Level3::class,
            Expression\Level3\Reserved::class,
            Expression\Level3\Fragment::class,
            Expression\Level3\Label::class,
            Expression\Level3\Path::class,
            Expression\Level3\Parameters::class,
            Expression\Level3\Query::class,
            Expression\Level3\QueryContinuation::class,
            Expression\Level4\Composite::class,
        ];
    }
}
