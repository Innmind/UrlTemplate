<?php
declare(strict_types = 1);

namespace Tests\Innmind\UrlTemplate;

use Innmind\UrlTemplate\{
    Expressions,
    Expression\Level4,
    Expression\Level3,
};
use Innmind\Immutable\Str;
use PHPUnit\Framework\TestCase;

class ExpressionsTest extends TestCase
{
    /**
     * @dataProvider cases
     */
    public function testOf($string, $expected)
    {
        $this->assertInstanceOf(
            $expected,
            Expressions::of(Str::of($string))->match(
                static fn($expression) => $expression,
                static fn() => null,
            ),
        );
        $this->assertSame(
            $string,
            Expressions::of(Str::of($string))->match(
                static fn($expression) => $expression->toString(),
                static fn() => null,
            ),
        );
    }

    public function testReturnNothingWhenInvalidPattern()
    {
        $this->assertNull(Expressions::of(Str::of('foo'))->match(
            static fn($expression) => $expression,
            static fn() => null,
        ));
    }

    public static function cases(): array
    {
        return [
            ['{foo}', Level4::class],
            ['{foo*}', Level4::class],
            ['{foo:42}', Level4::class],
            ['{+foo}', Level4\Reserved::class],
            ['{+foo*}', Level4\Reserved::class],
            ['{+foo:42}', Level4\Reserved::class],
            ['{#foo}', Level4\Fragment::class],
            ['{#foo*}', Level4\Fragment::class],
            ['{#foo:42}', Level4\Fragment::class],
            ['{.foo}', Level4\Label::class],
            ['{.foo*}', Level4\Label::class],
            ['{.foo:42}', Level4\Label::class],
            ['{/foo}', Level4\Path::class],
            ['{/foo*}', Level4\Path::class],
            ['{/foo:42}', Level4\Path::class],
            ['{;foo}', Level4\Parameters::class],
            ['{;foo*}', Level4\Parameters::class],
            ['{;foo:42}', Level4\Parameters::class],
            ['{?foo}', Level4\Query::class],
            ['{?foo*}', Level4\Query::class],
            ['{?foo:42}', Level4\Query::class],
            ['{&foo}', Level4\QueryContinuation::class],
            ['{&foo*}', Level4\QueryContinuation::class],
            ['{&foo:42}', Level4\QueryContinuation::class],
            ['{foo,bar}', Level3::class],
            ['{+foo,bar}', Level3\Reserved::class],
            ['{#foo,bar}', Level3\Fragment::class],
            ['{.foo,bar}', Level3\Label::class],
            ['{/foo,bar}', Level3\Path::class],
            ['{;foo,bar}', Level3\Parameters::class],
            ['{?foo,bar}', Level3\Query::class],
            ['{&foo,bar}', Level3\QueryContinuation::class],
            ['{foo*,bar}', Level4\Composite::class],
            ['{+foo*,bar}', Level4\Composite::class],
            ['{#foo*,bar}', Level4\Composite::class],
            ['{.foo*,bar}', Level4\Composite::class],
            ['{/foo*,bar}', Level4\Composite::class],
            ['{;foo*,bar}', Level4\Composite::class],
            ['{?foo*,bar}', Level4\Composite::class],
            ['{&foo*,bar}', Level4\Composite::class],
        ];
    }
}
