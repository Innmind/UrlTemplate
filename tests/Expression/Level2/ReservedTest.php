<?php
declare(strict_types = 1);

namespace Tests\Innmind\UrlTemplate\Expression\Level2;

use Innmind\UrlTemplate\{
    Expression\Level2\Reserved,
    Expression\Name,
    Expression,
    Exception\DomainException,
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
            new Reserved(new Name('foo'))
        );
    }

    public function testStringCast()
    {
        $this->assertSame('{+foo}', (string) new Reserved(new Name('foo')));
    }

    public function testExpand()
    {
        $expression = new Reserved(new Name('foo'));

        $this->assertSame('value', $expression->expand(
            (new Map('string', 'variable'))->put('foo', 'value')
        ));
        $this->assertSame('Hello%20World!', $expression->expand(
            (new Map('string', 'variable'))->put('foo', 'Hello World!')
        ));
        $this->assertSame('/foo/bar', $expression->expand(
            (new Map('string', 'variable'))->put('foo', '/foo/bar')
        ));
        $this->assertSame('', $expression->expand(
            new Map('string', 'variable')
        ));
    }

    public function testOf()
    {
        $this->assertInstanceOf(
            Reserved::class,
            $expression = Reserved::of(Str::of('{+foo}'))
        );
        $this->assertSame('{+foo}', (string) $expression);
    }

    public function testThrowWhenInvalidPattern()
    {
        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('foo');

        Reserved::of(Str::of('foo'));
    }
}
