<?php
declare(strict_types = 1);

namespace Tests\Innmind\UrlTemplate\Expression\Level4;

use Innmind\UrlTemplate\{
    Expression\Level4\QueryContinuation,
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

class QueryContinuationTest extends TestCase
{
    use BlackBox;

    public function testInterface()
    {
        $this->assertInstanceOf(
            Expression::class,
            QueryContinuation::of(Str::of('{&foo}')),
        );
        $this->assertInstanceOf(
            Expression::class,
            QueryContinuation::of(Str::of('{&foo*}')),
        );
        $this->assertInstanceOf(
            Expression::class,
            QueryContinuation::of(Str::of('{&foo:42}')),
        );
    }

    public function testStringCast()
    {
        $this->assertSame('{&foo}', QueryContinuation::of(Str::of('{&foo}'))->toString());
        $this->assertSame('{&foo*}', QueryContinuation::of(Str::of('{&foo*}'))->toString());
        $this->assertSame('{&foo:42}', QueryContinuation::of(Str::of('{&foo:42}'))->toString());
    }

    public function testThrowWhenNegativeLimit()
    {
        $this
            ->forAll(Set\Integers::below(1))
            ->then(function(int $int): void {
                $this->expectException(DomainException::class);

                QueryContinuation::of(Str::of("{&foo:$int}"));
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
            '&var=val',
            QueryContinuation::of(Str::of('{&var:3}'))->expand($variables),
        );
        $this->assertSame(
            '&list=red,green,blue',
            QueryContinuation::of(Str::of('{&list}'))->expand($variables),
        );
        $this->assertSame(
            '&list=red&list=green&list=blue',
            QueryContinuation::of(Str::of('{&list*}'))->expand($variables),
        );
        $this->assertSame(
            '&keys=semi,%3B,dot,.,comma,%2C',
            QueryContinuation::of(Str::of('{&keys}'))->expand($variables),
        );
        $this->assertSame(
            '&semi=%3B&dot=.&comma=%2C',
            QueryContinuation::of(Str::of('{&keys*}'))->expand($variables),
        );
    }

    public function testOf()
    {
        $this->assertInstanceOf(
            QueryContinuation::class,
            $expression = QueryContinuation::of(Str::of('{&foo}')),
        );
        $this->assertSame('{&foo}', $expression->toString());
        $this->assertInstanceOf(
            QueryContinuation::class,
            $expression = QueryContinuation::of(Str::of('{&foo*}')),
        );
        $this->assertSame('{&foo*}', $expression->toString());
        $this->assertInstanceOf(
            QueryContinuation::class,
            $expression = QueryContinuation::of(Str::of('{&foo:42}')),
        );
        $this->assertSame('{&foo:42}', $expression->toString());
    }

    public function testThrowWhenInvalidPattern()
    {
        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('{foo}');

        QueryContinuation::of(Str::of('{foo}'));
    }

    public function testThrowExplodeRegex()
    {
        $this->expectException(LogicException::class);

        QueryContinuation::of(Str::of('{&foo*}'))->regex();
    }

    public function testRegex()
    {
        $this->assertSame(
            '\&foo=(?<foo>[a-zA-Z0-9\%\-\.\_\~]*)',
            QueryContinuation::of(Str::of('{&foo}'))->regex(),
        );
        $this->assertSame(
            '\&foo=(?<foo>[a-zA-Z0-9\%\-\.\_\~]{2})',
            QueryContinuation::of(Str::of('{&foo:2}'))->regex(),
        );
    }
}
