<?php
declare(strict_types = 1);

namespace Tests\Innmind\UrlTemplate\Expression\Level2;

use Innmind\UrlTemplate\{
    Expression\Level2\Reserved,
    Expression\Name,
    Expression,
    Exception\DomainException,
    Exception\OnlyScalarCanBeExpandedForExpression,
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
            new Reserved(new Name('foo')),
        );
    }

    public function testStringCast()
    {
        $this->assertSame('{+foo}', (new Reserved(new Name('foo')))->toString());
    }

    public function testExpand()
    {
        $expression = new Reserved(new Name('foo'));

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
            $expression = Reserved::of(Str::of('{+foo}')),
        );
        $this->assertSame('{+foo}', $expression->toString());
    }

    public function testThrowWhenInvalidPattern()
    {
        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('foo');

        Reserved::of(Str::of('foo'));
    }

    public function testRegex()
    {
        $this->assertSame(
            '(?<foo>[a-zA-Z0-9\%:/\?#\[\]@!$&\'\(\)\*\+,;=\-\.\_\~]*)',
            Reserved::of(Str::of('{+foo}'))->regex(),
        );
    }

    public function testThrowWhenTryingToExpandWithAnArray()
    {
        $expression = new Reserved(new Name('foo'));

        $this->expectException(OnlyScalarCanBeExpandedForExpression::class);
        $this->expectExceptionMessage('foo');

        $expression->expand(
            Map::of(['foo', ['value']]),
        );
    }
}
