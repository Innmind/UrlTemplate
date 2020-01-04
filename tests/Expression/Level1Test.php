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
        $this->assertSame('{foo}', (new Level1(new Name('foo')))->toString());
    }

    public function testExpand()
    {
        $expression = new Level1(new Name('foo'));

        $this->assertSame('value', $expression->expand(
            Map::of('string', 'variable')('foo', 'value')
        ));
        $this->assertSame('Hello%20World%21', $expression->expand(
            Map::of('string', 'variable')('foo', 'Hello World!')
        ));
        $this->assertSame('', $expression->expand(
            Map::of('string', 'variable')
        ));
    }

    public function testOf()
    {
        $this->assertInstanceOf(
            Level1::class,
            $expression = Level1::of(Str::of('{foo}'))
        );
        $this->assertSame('{foo}', $expression->toString());
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
            '(?<foo>[a-zA-Z0-9\%\-\.\_\~]*)',
            Level1::of(Str::of('{foo}'))->regex()
        );
    }
}
