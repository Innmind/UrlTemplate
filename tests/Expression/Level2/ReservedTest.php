<?php
declare(strict_types = 1);

namespace Tests\Innmind\UrlTemplate\Expression\Level2;

use Innmind\UrlTemplate\{
    Expression\Level2\Reserved,
    Expression,
};
use Innmind\Immutable\{
    Map,
    Str,
};
use PHPUnit\Framework\TestCase;

class ReservedTest extends TestCase
{
    public function testInterface()
    {
        $this->assertInstanceOf(
            Expression::class,
            Reserved::of(Str::of('{+foo}'))->match(
                static fn($expression) => $expression,
                static fn() => null,
            ),
        );
    }

    public function testStringCast()
    {
        $this->assertSame(
            '{+foo}',
            Reserved::of(Str::of('{+foo}'))->match(
                static fn($expression) => $expression->toString(),
                static fn() => null,
            ),
        );
    }

    public function testExpand()
    {
        $expression = Reserved::of(Str::of('{+foo}'))->match(
            static fn($expression) => $expression,
            static fn() => null,
        );

        $this->assertSame('value', $expression->expand(
            Map::of(['foo', 'value']),
        ));
        $this->assertSame('Hello%20World!', $expression->expand(
            Map::of(['foo', 'Hello World!']),
        ));
        $this->assertSame('/foo/bar', $expression->expand(
            Map::of(['foo', '/foo/bar']),
        ));
        $this->assertSame('', $expression->expand(
            Map::of(),
        ));
    }

    public function testOf()
    {
        $this->assertInstanceOf(
            Reserved::class,
            $expression = Reserved::of(Str::of('{+foo}'))->match(
                static fn($expression) => $expression,
                static fn() => null,
            ),
        );
        $this->assertSame('{+foo}', $expression->toString());
    }

    public function testReturnNothingWhenInvalidPattern()
    {
        $this->assertNull(Reserved::of(Str::of('foo'))->match(
            static fn($expression) => $expression,
            static fn() => null,
        ));
    }

    public function testRegex()
    {
        $this->assertSame(
            '(?<foo>[a-zA-Z0-9\%:/\?#\[\]@!$&\'\(\)\*\+,;=\-\.\_\~]*)',
            Reserved::of(Str::of('{+foo}'))->match(
                static fn($expression) => $expression->regex(),
                static fn() => null,
            ),
        );
    }

    public function testReturnEmptyStringWhenTryingToExpandWithAnArray()
    {
        $expression = Reserved::of(Str::of('{+foo}'))->match(
            static fn($expression) => $expression,
            static fn() => null,
        );

        $this->assertSame('', $expression->expand(
            Map::of(['foo', ['value']]),
        ));
    }
}
