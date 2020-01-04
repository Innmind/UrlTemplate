<?php
declare(strict_types = 1);

namespace Tests\Innmind\UrlTemplate\Expression\Level4;

use Innmind\UrlTemplate\{
    Expression\Level4\Query,
    Expression\Name,
    Expression,
    Exception\DomainException,
    Exception\LogicException,
};
use Innmind\Immutable\{
    Map,
    Str,
};
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
        $this->assertSame('{?foo}', (new Query(new Name('foo')))->toString());
        $this->assertSame('{?foo*}', Query::explode(new Name('foo'))->toString());
        $this->assertSame('{?foo:42}', Query::limit(new Name('foo'), 42)->toString());
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
        $variables = Map::of('string', 'variable')
            ('var', 'value')
            ('hello', 'Hello World!')
            ('path', '/foo/bar')
            ('list', ['red', 'green', 'blue'])
            ('keys', [['semi', ';'], ['dot', '.'], ['comma', ',']]);

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

    public function testOf()
    {
        $this->assertInstanceOf(
            Query::class,
            $expression = Query::of(Str::of('{?foo}'))
        );
        $this->assertSame('{?foo}', $expression->toString());
        $this->assertInstanceOf(
            Query::class,
            $expression = Query::of(Str::of('{?foo*}'))
        );
        $this->assertSame('{?foo*}', $expression->toString());
        $this->assertInstanceOf(
            Query::class,
            $expression = Query::of(Str::of('{?foo:42}'))
        );
        $this->assertSame('{?foo:42}', $expression->toString());
    }

    public function testThrowWhenInvalidPattern()
    {
        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('{foo}');

        Query::of(Str::of('{foo}'));
    }

    public function testThrowExplodeRegex()
    {
        $this->expectException(LogicException::class);

        Query::of(Str::of('{?foo*}'))->regex();
    }

    public function testRegex()
    {
        $this->assertSame(
            '\?foo=(?<foo>[a-zA-Z0-9\%\-\.\_\~]*)',
            Query::of(Str::of('{?foo}'))->regex()
        );
        $this->assertSame(
            '\?foo=(?<foo>[a-zA-Z0-9\%\-\.\_\~]{2})',
            Query::of(Str::of('{?foo:2}'))->regex()
        );
    }
}
