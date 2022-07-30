<?php
declare(strict_types = 1);

namespace Tests\Innmind\UrlTemplate\Expression\Level3;

use Innmind\UrlTemplate\{
    Expression\Level3\Reserved,
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
            Reserved::of(Str::of('{+foo,bar}')),
        );
    }

    public function testStringCast()
    {
        $this->assertSame(
            '{+foo,bar}',
            Reserved::of(Str::of('{+foo,bar}'))->toString(),
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
            '1024,Hello%20World!,768',
            Reserved::of(Str::of('{+x,hello,y}'))->expand($variables),
        );
        $this->assertSame(
            '/foo/bar,1024',
            Reserved::of(Str::of('{+path,x}'))->expand($variables),
        );
    }

    public function testOf()
    {
        $this->assertInstanceOf(
            Reserved::class,
            $expression = Reserved::of(Str::of('{+foo,bar}')),
        );
        $this->assertSame('{+foo,bar}', $expression->toString());
    }

    public function testThrowWhenInvalidPattern()
    {
        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('{foo}');

        Reserved::of(Str::of('{foo}'));
    }

    public function testRegex()
    {
        $this->assertSame(
            '(?<foo>[a-zA-Z0-9\%:/\?#\[\]@!$&\'\(\)\*\+,;=\-\.\_\~]*),(?<bar>[a-zA-Z0-9\%:/\?#\[\]@!$&\'\(\)\*\+,;=\-\.\_\~]*)',
            Reserved::of(Str::of('{+foo,bar}'))->regex(),
        );
    }
}
