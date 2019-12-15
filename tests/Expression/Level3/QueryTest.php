<?php
declare(strict_types = 1);

namespace Tests\Innmind\UrlTemplate\Expression\Level3;

use Innmind\UrlTemplate\{
    Expression\Level3\Query,
    Expression\Name,
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
            new Query(new Name('foo'), new Name('bar'))
        );
    }

    public function testStringCast()
    {
        $this->assertSame(
            '{?foo,bar}',
            (string) new Query(new Name('foo'), new Name('bar'))
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
            '?x=1024&y=768',
            (new Query(new Name('x'), new Name('y')))->expand($variables)
        );
        $this->assertSame(
            '?x=1024&y=768&empty=',
            (new Query(new Name('x'), new Name('y'), new Name('empty')))->expand($variables)
        );
    }

    public function testOf()
    {
        $this->assertInstanceOf(
            Query::class,
            $expression = Query::of(Str::of('{?foo,bar}'))
        );
        $this->assertSame('{?foo,bar}', (string) $expression);
    }

    public function testThrowWhenInvalidPattern()
    {
        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('{?foo}');

        Query::of(Str::of('{?foo}'));
    }

    public function testRegex()
    {
        $this->assertSame(
            '\?foo=(?<foo>[a-zA-Z0-9\%\-\.\_\~]*)\&bar=(?<bar>[a-zA-Z0-9\%\-\.\_\~]*)',
            Query::of(Str::of('{?foo,bar}'))->regex()
        );
    }
}
