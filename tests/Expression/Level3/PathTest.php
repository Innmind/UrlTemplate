<?php
declare(strict_types = 1);

namespace Tests\Innmind\UrlTemplate\Expression\Level3;

use Innmind\UrlTemplate\{
    Expression\Level3\Path,
    Expression,
    Exception\DomainException,
};
use Innmind\Immutable\{
    Map,
    Str,
};
use PHPUnit\Framework\TestCase;

class PathTest extends TestCase
{
    public function testInterface()
    {
        $this->assertInstanceOf(
            Expression::class,
            Path::of(Str::of('{/foo,bar}')),
        );
    }

    public function testStringCast()
    {
        $this->assertSame(
            '{/foo,bar}',
            Path::of(Str::of('{/foo,bar}'))->toString(),
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
            '/value',
            Path::of(Str::of('{/var}'))->expand($variables),
        );
        $this->assertSame(
            '/value/1024',
            Path::of(Str::of('{/var,x}'))->expand($variables),
        );
    }

    public function testOf()
    {
        $this->assertInstanceOf(
            Path::class,
            $expression = Path::of(Str::of('{/foo,bar}')),
        );
        $this->assertSame('{/foo,bar}', $expression->toString());
    }

    public function testThrowWhenInvalidPattern()
    {
        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('{foo}');

        Path::of(Str::of('{foo}'));
    }

    public function testRegex()
    {
        $this->assertSame(
            '/(?<foo>[a-zA-Z0-9\%\-\.\_\~]*)/(?<bar>[a-zA-Z0-9\%\-\.\_\~]*)',
            Path::of(Str::of('{/foo,bar}'))->regex(),
        );
    }
}
