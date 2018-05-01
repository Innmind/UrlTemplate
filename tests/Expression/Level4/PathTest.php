<?php
declare(strict_types = 1);

namespace Tests\Innmind\UrlTemplate\Expression\Level4;

use Innmind\UrlTemplate\{
    Expression\Level4\Path,
    Expression\Name,
    Expression,
    Exception\DomainException,
};
use Innmind\Immutable\Map;
use PHPUnit\Framework\TestCase;
use Eris\{
    Generator,
    TestTrait,
};

class PathTest extends TestCase
{
    use TestTrait;

    public function testInterface()
    {
        $this->assertInstanceOf(
            Expression::class,
            new Path(new Name('foo'))
        );
        $this->assertInstanceOf(
            Expression::class,
            Path::explode(new Name('foo'))
        );
        $this->assertInstanceOf(
            Expression::class,
            Path::limit(new Name('foo'), 42)
        );
    }

    public function testStringCast()
    {
        $this->assertSame('{/foo}', (string) new Path(new Name('foo')));
        $this->assertSame('{/foo*}', (string) Path::explode(new Name('foo')));
        $this->assertSame('{/foo:42}', (string) Path::limit(new Name('foo'), 42));
    }

    public function testThrowWhenNegativeLimit()
    {
        $this
            ->forAll(Generator\neg())
            ->then(function(int $int): void {
                $this->expectException(DomainException::class);

                Path::limit(new Name('foo'), $int);
            });
    }

    public function testExpand()
    {
        $variables = (new Map('string', 'variable'))
            ->put('var', 'value')
            ->put('hello', 'Hello World!')
            ->put('path', '/foo/bar')
            ->put('list', ['red', 'green', 'blue'])
            ->put('keys', [['semi', ';'], ['dot', '.'], ['comma', ',']]);

        $this->assertSame(
            '/red,green,blue',
            (new Path(new Name('list')))->expand($variables)
        );
        $this->assertSame(
            '/red/green/blue',
            Path::explode(new Name('list'))->expand($variables)
        );
        $this->assertSame(
            '/semi,%3B,dot,.,comma,%2C',
            (new Path(new Name('keys')))->expand($variables)
        );
        $this->assertSame(
            '/semi=%3B/dot=./comma=%2C',
            Path::explode(new Name('keys'))->expand($variables)
        );
    }
}