<?php
declare(strict_types = 1);

namespace Tests\Innmind\UrlTemplate\Expression;

use Innmind\UrlTemplate\{
    Expression\Level3,
    Expression\Name,
    Expression,
    Exception\DomainException,
};
use Innmind\Immutable\{
    Map,
    Str,
};
use PHPUnit\Framework\TestCase;

class Level3Test extends TestCase
{
    public function testInterface()
    {
        $this->assertInstanceOf(
            Expression::class,
            new Level3(new Name('foo'), new Name('bar'))
        );
    }

    public function testStringCast()
    {
        $this->assertSame(
            '{foo,bar}',
            (new Level3(new Name('foo'), new Name('bar')))->toString(),
        );
    }

    public function testExpand()
    {
        $variables = Map::of('string', 'variable')
            ('var', 'value')
            ('hello', 'Hello World!')
            ('empty', '')
            ('path', '/foo/bar')
            ('x', '1024')
            ('y', '768');

        $this->assertSame(
            '1024,768',
            (new Level3(new Name('x'), new Name('y')))->expand($variables)
        );
        $this->assertSame(
            '1024,Hello%20World%21,768',
            (new Level3(new Name('x'), new Name('hello'), new Name('y')))->expand($variables)
        );
    }

    public function testOf()
    {
        $this->assertInstanceOf(
            Level3::class,
            $expression = Level3::of(Str::of('{foo,bar}'))
        );
        $this->assertSame('{foo,bar}', $expression->toString());
    }

    public function testThrowWhenInvalidPattern()
    {
        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('{foo}');

        Level3::of(Str::of('{foo}'));
    }

    public function testRegex()
    {
        $this->assertSame(
            '(?<foo>[a-zA-Z0-9\%\-\.\_\~]*),(?<bar>[a-zA-Z0-9\%\-\.\_\~]*)',
            Level3::of(Str::of('{foo,bar}'))->regex()
        );
    }
}
