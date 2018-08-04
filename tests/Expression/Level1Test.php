<?php
declare(strict_types = 1);

namespace Tests\Innmind\UrlTemplate\Expression;

use Innmind\UrlTemplate\{
    Expression\Level1,
    Expression\Name,
    Expression,
    Exception\DomainException,
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
            new Level1(new Name('foo'))
        );
    }

    public function testStringCast()
    {
        $this->assertSame('{foo}', (string) new Level1(new Name('foo')));
    }

    public function testExpand()
    {
        $expression = new Level1(new Name('foo'));

        $this->assertSame('value', $expression->expand(
            (new Map('string', 'variable'))->put('foo', 'value')
        ));
        $this->assertSame('Hello%20World%21', $expression->expand(
            (new Map('string', 'variable'))->put('foo', 'Hello World!')
        ));
        $this->assertSame('', $expression->expand(
            new Map('string', 'variable')
        ));
    }

    public function testOf()
    {
        $this->assertInstanceOf(
            Level1::class,
            $expression = Level1::of(Str::of('{foo}'))
        );
        $this->assertSame('{foo}', (string) $expression);
    }

    public function testThrowWhenInvalidPattern()
    {
        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('foo');

        Level1::of(Str::of('foo'));
    }

    public function testRegex()
    {
        $this->assertSame(
            '(?<foo>[a-zA-Z0-9\%]*)',
            Level1::of(Str::of('{foo}'))->regex()
        );
    }
}
