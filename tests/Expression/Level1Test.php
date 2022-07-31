<?php
declare(strict_types = 1);

namespace Tests\Innmind\UrlTemplate\Expression;

use Innmind\UrlTemplate\{
    Expression\Level1,
    Expression,
    Exception\OnlyScalarCanBeExpandedForExpression,
};
use Innmind\Immutable\{
    Map,
    Str,
};
use PHPUnit\Framework\TestCase;

class Level1Test extends TestCase
{
    public function testInterface()
    {
        $this->assertInstanceOf(
            Expression::class,
            Level1::of(Str::of('{foo}'))->match(
                static fn($expression) => $expression,
                static fn() => null,
            ),
        );
    }

    public function testStringCast()
    {
        $this->assertSame(
            '{foo}',
            Level1::of(Str::of('{foo}'))->match(
                static fn($expression) => $expression->toString(),
                static fn() => null,
            ),
        );
    }

    public function testExpand()
    {
        $expression = Level1::of(Str::of('{foo}'))->match(
            static fn($expression) => $expression,
            static fn() => null,
        );

        $this->assertSame('value', $expression->expand(
            Map::of(['foo', 'value']),
        ));
        $this->assertSame('Hello%20World%21', $expression->expand(
            Map::of(['foo', 'Hello World!']),
        ));
        $this->assertSame('', $expression->expand(
            Map::of(),
        ));
    }

    public function testOf()
    {
        $this->assertInstanceOf(
            Level1::class,
            $expression = Level1::of(Str::of('{foo}'))->match(
                static fn($expression) => $expression,
                static fn() => null,
            ),
        );
        $this->assertSame('{foo}', $expression->toString());
    }

    public function testReturnNothingWhenInvalidPattern()
    {
        $this->assertNull(Level1::of(Str::of('foo'))->match(
            static fn($expression) => $expression,
            static fn() => null,
        ));
    }

    public function testRegex()
    {
        $this->assertSame(
            '(?<foo>[a-zA-Z0-9\%\-\.\_\~]*)',
            Level1::of(Str::of('{foo}'))->match(
                static fn($expression) => $expression->regex(),
                static fn() => null,
            ),
        );
    }

    public function testThrowWhenTryingToExpandWithAnArray()
    {
        $expression = Level1::of(Str::of('{foo}'))->match(
            static fn($expression) => $expression,
            static fn() => null,
        );

        $this->expectException(OnlyScalarCanBeExpandedForExpression::class);
        $this->expectExceptionMessage('foo');

        $expression->expand(
            Map::of(['foo', ['value']]),
        );
    }
}
