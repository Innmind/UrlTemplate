<?php
declare(strict_types = 1);

namespace Tests\Innmind\UrlTemplate\Expression\Level3;

use Innmind\UrlTemplate\{
    Expression\Level3\Label,
    Expression,
    Exception\DomainException,
};
use Innmind\Immutable\{
    Map,
    Str,
};
use PHPUnit\Framework\TestCase;

class LabelTest extends TestCase
{
    public function testInterface()
    {
        $this->assertInstanceOf(
            Expression::class,
            Label::of(Str::of('{.foo,bar}')),
        );
    }

    public function testStringCast()
    {
        $this->assertSame(
            '{.foo,bar}',
            Label::of(Str::of('{.foo,bar}'))->toString(),
        );
    }

    public function testExpand()
    {
        $variables = Map::of()
            ('var', 'value')
            ('hello', 'Hello World!')
            ('empty', '')
            ('path', '/foo/bar')
            ('x', '1024')
            ('y', '768');

        $this->assertSame(
            '.1024.768',
            Label::of(Str::of('{.x,y}'))->expand($variables),
        );
        $this->assertSame(
            '.value',
            Label::of(Str::of('{.var}'))->expand($variables),
        );
    }

    public function testOf()
    {
        $this->assertInstanceOf(
            Label::class,
            $expression = Label::of(Str::of('{.foo,bar}')),
        );
        $this->assertSame('{.foo,bar}', $expression->toString());
    }

    public function testThrowWhenInvalidPattern()
    {
        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('{foo}');

        Label::of(Str::of('{foo}'));
    }

    public function testRegex()
    {
        $this->assertSame(
            '\.(?<foo>[a-zA-Z0-9\%\-\_\~]*).(?<bar>[a-zA-Z0-9\%\-\_\~]*)',
            Label::of(Str::of('{.foo,bar}'))->regex(),
        );
    }
}
