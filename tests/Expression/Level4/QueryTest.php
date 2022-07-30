<?php
declare(strict_types = 1);

namespace Tests\Innmind\UrlTemplate\Expression\Level4;

use Innmind\UrlTemplate\{
    Expression\Level4\Query,
    Expression,
    Exception\DomainException,
    Exception\LogicException,
};
use Innmind\Immutable\{
    Map,
    Str,
};
use PHPUnit\Framework\TestCase;
use Innmind\BlackBox\{
    PHPUnit\BlackBox,
    Set,
};

class QueryTest extends TestCase
{
    use BlackBox;

    public function testInterface()
    {
        $this->assertInstanceOf(
            Expression::class,
            Query::of(Str::of('{?foo}')),
        );
        $this->assertInstanceOf(
            Expression::class,
            Query::of(Str::of('{?foo*}')),
        );
        $this->assertInstanceOf(
            Expression::class,
            Query::of(Str::of('{?foo:42}')),
        );
    }

    public function testStringCast()
    {
        $this->assertSame('{?foo}', Query::of(Str::of('{?foo}'))->toString());
        $this->assertSame('{?foo*}', Query::of(Str::of('{?foo*}'))->toString());
        $this->assertSame('{?foo:42}', Query::of(Str::of('{?foo:42}'))->toString());
    }

    public function testThrowWhenNegativeLimit()
    {
        $this
            ->forAll(Set\Integers::below(1))
            ->then(function(int $int): void {
                $this->expectException(DomainException::class);

                Query::of(Str::of("{?foo:$int}"));
            });
    }

    public function testExpand()
    {
        $variables = Map::of()
            ('var', 'value')
            ('hello', 'Hello World!')
            ('path', '/foo/bar')
            ('list', ['red', 'green', 'blue'])
            ('keys', [['semi', ';'], ['dot', '.'], ['comma', ',']]);

        $this->assertSame(
            '?var=val',
            Query::of(Str::of('{?var:3}'))->expand($variables),
        );
        $this->assertSame(
            '?list=red,green,blue',
            Query::of(Str::of('{?list}'))->expand($variables),
        );
        $this->assertSame(
            '?list=red&list=green&list=blue',
            Query::of(Str::of('{?list*}'))->expand($variables),
        );
        $this->assertSame(
            '?keys=semi,%3B,dot,.,comma,%2C',
            Query::of(Str::of('{?keys}'))->expand($variables),
        );
        $this->assertSame(
            '?semi=%3B&dot=.&comma=%2C',
            Query::of(Str::of('{?keys*}'))->expand($variables),
        );
    }

    public function testOf()
    {
        $this->assertInstanceOf(
            Query::class,
            $expression = Query::of(Str::of('{?foo}')),
        );
        $this->assertSame('{?foo}', $expression->toString());
        $this->assertInstanceOf(
            Query::class,
            $expression = Query::of(Str::of('{?foo*}')),
        );
        $this->assertSame('{?foo*}', $expression->toString());
        $this->assertInstanceOf(
            Query::class,
            $expression = Query::of(Str::of('{?foo:42}')),
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
            Query::of(Str::of('{?foo}'))->regex(),
        );
        $this->assertSame(
            '\?foo=(?<foo>[a-zA-Z0-9\%\-\.\_\~]{2})',
            Query::of(Str::of('{?foo:2}'))->regex(),
        );
    }
}
