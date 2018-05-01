<?php
declare(strict_types = 1);

namespace Tests\Innmind\UrlTemplate\Expression\Level4;

use Innmind\UrlTemplate\{
    Expression\Level4\QueryContinuation,
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

class QueryContinuationTest extends TestCase
{
    use TestTrait;

    public function testInterface()
    {
        $this->assertInstanceOf(
            Expression::class,
            new QueryContinuation(new Name('foo'))
        );
        $this->assertInstanceOf(
            Expression::class,
            QueryContinuation::explode(new Name('foo'))
        );
        $this->assertInstanceOf(
            Expression::class,
            QueryContinuation::limit(new Name('foo'), 42)
        );
    }

    public function testStringCast()
    {
        $this->assertSame('{&foo}', (string) new QueryContinuation(new Name('foo')));
        $this->assertSame('{&foo*}', (string) QueryContinuation::explode(new Name('foo')));
        $this->assertSame('{&foo:42}', (string) QueryContinuation::limit(new Name('foo'), 42));
    }

    public function testThrowWhenNegativeLimit()
    {
        $this
            ->forAll(Generator\neg())
            ->then(function(int $int): void {
                $this->expectException(DomainException::class);

                QueryContinuation::limit(new Name('foo'), $int);
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
            '&var=val',
            QueryContinuation::limit(new Name('var'), 3)->expand($variables)
        );
        $this->assertSame(
            '&list=red,green,blue',
            (new QueryContinuation(new Name('list')))->expand($variables)
        );
        $this->assertSame(
            '&list=red&list=green&list=blue',
            QueryContinuation::explode(new Name('list'))->expand($variables)
        );
        $this->assertSame(
            '&keys=semi,%3B,dot,.,comma,%2C',
            (new QueryContinuation(new Name('keys')))->expand($variables)
        );
        $this->assertSame(
            '&semi=%3B&dot=.&comma=%2C',
            QueryContinuation::explode(new Name('keys'))->expand($variables)
        );
    }
}
