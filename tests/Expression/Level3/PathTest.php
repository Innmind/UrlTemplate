<?php
declare(strict_types = 1);

namespace Tests\Innmind\UrlTemplate\Expression\Level3;

use Innmind\UrlTemplate\{
    Expression\Level3\Path,
    Expression\Name,
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
            new Path(new Name('foo'), new Name('bar'))
        );
    }

    public function testStringCast()
    {
        $this->assertSame(
            '{/foo,bar}',
            (string) new Path(new Name('foo'), new Name('bar'))
        );
    }

    public function testExpand()
    {
        $variables = (new Map('string', 'variable'))
            ->put('var', 'value')
            ->put('hello', 'Hello World!')
            ->put('empty', '')
            ->put('path', '/foo/bar')
            ->put('x', '1024')
            ->put('y', '768');

        $this->assertSame(
            '/value',
            (new Path(new Name('var')))->expand($variables)
        );
        $this->assertSame(
            '/value/1024',
            (new Path(new Name('var'), new Name('x')))->expand($variables)
        );
    }

    public function testOf()
    {
        $this->assertInstanceOf(
            Path::class,
            $expression = Path::of(Str::of('{/foo,bar}'))
        );
        $this->assertSame('{/foo,bar}', (string) $expression);
    }

    public function testThrowWhenInvalidPattern()
    {
        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('{/foo}');

        Path::of(Str::of('{/foo}'));
    }
}
