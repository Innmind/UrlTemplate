<?php
declare(strict_types = 1);

namespace Tests\Innmind\UrlTemplate\Expression\Level3;

use Innmind\UrlTemplate\{
    Expression\Level3\Query,
    Expression,
    Exception\DomainException,
};
use Innmind\Immutable\{
    Map,
    Str,
};
use PHPUnit\Framework\TestCase;

class QueryTest extends TestCase
{
    public function testInterface()
    {
        $this->assertInstanceOf(
            Expression::class,
            Query::of(Str::of('{?foo,bar}')),
        );
    }

    public function testStringCast()
    {
        $this->assertSame(
            '{?foo,bar}',
            Query::of(Str::of('{?foo,bar}'))->toString(),
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
            '?x=1024&y=768',
            Query::of(Str::of('{?x,y}'))->expand($variables),
        );
        $this->assertSame(
            '?x=1024&y=768&empty=',
            Query::of(Str::of('{?x,y,empty}'))->expand($variables),
        );
    }

    public function testOf()
    {
        $this->assertInstanceOf(
            Query::class,
            $expression = Query::of(Str::of('{?foo,bar}')),
        );
        $this->assertSame('{?foo,bar}', $expression->toString());
    }

    public function testThrowWhenInvalidPattern()
    {
        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('{foo}');

        Query::of(Str::of('{foo}'));
    }

    public function testRegex()
    {
        $this->assertSame(
            '\?foo=(?<foo>[a-zA-Z0-9\%\-\.\_\~]*)\&bar=(?<bar>[a-zA-Z0-9\%\-\.\_\~]*)',
            Query::of(Str::of('{?foo,bar}'))->regex(),
        );
    }
}
