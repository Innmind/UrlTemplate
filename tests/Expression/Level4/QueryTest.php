<?php
declare(strict_types = 1);

namespace Tests\Innmind\UrlTemplate\Expression\Level4;

use Innmind\UrlTemplate\{
    Expression\Level4\Query,
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

class QueryTest extends TestCase
{
    use TestTrait;

    public function testInterface()
    {
        $this->assertInstanceOf(
            Expression::class,
            new Query(new Name('foo'))
        );
        $this->assertInstanceOf(
            Expression::class,
            Query::explode(new Name('foo'))
        );
        $this->assertInstanceOf(
            Expression::class,
            Query::limit(new Name('foo'), 42)
        );
    }

    public function testStringCast()
    {
        $this->assertSame('{?foo}', (string) new Query(new Name('foo')));
        $this->assertSame('{?foo*}', (string) Query::explode(new Name('foo')));
        $this->assertSame('{?foo:42}', (string) Query::limit(new Name('foo'), 42));
    }

    public function testThrowWhenNegativeLimit()
    {
        $this
            ->forAll(Generator\neg())
            ->then(function(int $int): void {
                $this->expectException(DomainException::class);

                Query::limit(new Name('foo'), $int);
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
            '?var=val',
            Query::limit(new Name('var'), 3)->expand($variables)
        );
        $this->assertSame(
            '?list=red,green,blue',
            (new Query(new Name('list')))->expand($variables)
        );
        $this->assertSame(
            '?list=red&list=green&list=blue',
            Query::explode(new Name('list'))->expand($variables)
        );
        $this->assertSame(
            '?keys=semi,%3B,dot,.,comma,%2C',
            (new Query(new Name('keys')))->expand($variables)
        );
        $this->assertSame(
            '?semi=%3B&dot=.&comma=%2C',
            Query::explode(new Name('keys'))->expand($variables)
        );
    }
}
