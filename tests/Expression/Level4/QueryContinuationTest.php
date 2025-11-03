<?php
declare(strict_types = 1);

namespace Tests\Innmind\UrlTemplate\Expression\Level4;

use Innmind\UrlTemplate\{
    Expression\Level4\QueryContinuation,
    Expression\Name,
    Expression,
    Exception\LogicException,
};
use Innmind\Immutable\{
    Map,
    Str,
};
use Innmind\BlackBox\{
    PHPUnit\BlackBox,
    PHPUnit\Framework\TestCase,
    Set,
};

class QueryContinuationTest extends TestCase
{
    use BlackBox;

    public function testInterface()
    {
        $this->assertInstanceOf(
            Expression::class,
            QueryContinuation::of(Str::of('{&foo}'))->match(
                static fn($expression) => $expression,
                static fn() => null,
            ),
        );
        $this->assertInstanceOf(
            Expression::class,
            QueryContinuation::of(Str::of('{&foo*}'))->match(
                static fn($expression) => $expression,
                static fn() => null,
            ),
        );
        $this->assertInstanceOf(
            Expression::class,
            QueryContinuation::of(Str::of('{&foo:42}'))->match(
                static fn($expression) => $expression,
                static fn() => null,
            ),
        );
    }

    public function testStringCast()
    {
        $this->assertSame(
            '{&foo}',
            QueryContinuation::of(Str::of('{&foo}'))->match(
                static fn($expression) => $expression->toString(),
                static fn() => null,
            ),
        );
        $this->assertSame(
            '{&foo*}',
            QueryContinuation::of(Str::of('{&foo*}'))->match(
                static fn($expression) => $expression->toString(),
                static fn() => null,
            ),
        );
        $this->assertSame(
            '{&foo:42}',
            QueryContinuation::of(Str::of('{&foo:42}'))->match(
                static fn($expression) => $expression->toString(),
                static fn() => null,
            ),
        );
    }

    public function testReturnNothingWhenNegativeLimit(): BlackBox\Proof
    {
        return $this
            ->forAll(Set::integers()->below(1))
            ->prove(function(int $int): void {
                $this->assertNull(QueryContinuation::of(Str::of("{&foo:$int}"))->match(
                    static fn($expression) => $expression,
                    static fn() => null,
                ));
            });
    }

    public function testExpand()
    {
        $values = Map::of()
            ('var', 'value')
            ('hello', 'Hello World!')
            ('path', '/foo/bar');
        $lists = Map::of()
            ('list', ['red', 'green', 'blue']);
        $keys = Map::of()
            ('keys', [
                [Name::of('semi'), ';'],
                [Name::of('dot'), '.'],
                [Name::of('comma'), ','],
            ]);

        $this->assertSame(
            '&var=val',
            QueryContinuation::of(Str::of('{&var:3}'))->match(
                static fn($expression) => $expression->expand($values, $lists, $keys),
                static fn() => null,
            ),
        );
        $this->assertSame(
            '&list=red,green,blue',
            QueryContinuation::of(Str::of('{&list}'))->match(
                static fn($expression) => $expression->expand($values, $lists, $keys),
                static fn() => null,
            ),
        );
        $this->assertSame(
            '&list=red&list=green&list=blue',
            QueryContinuation::of(Str::of('{&list*}'))->match(
                static fn($expression) => $expression->expand($values, $lists, $keys),
                static fn() => null,
            ),
        );
        $this->assertSame(
            '&keys=semi,%3B,dot,.,comma,%2C',
            QueryContinuation::of(Str::of('{&keys}'))->match(
                static fn($expression) => $expression->expand($values, $lists, $keys),
                static fn() => null,
            ),
        );
        $this->assertSame(
            '&semi=%3B&dot=.&comma=%2C',
            QueryContinuation::of(Str::of('{&keys*}'))->match(
                static fn($expression) => $expression->expand($values, $lists, $keys),
                static fn() => null,
            ),
        );
    }

    public function testOf()
    {
        $this->assertInstanceOf(
            QueryContinuation::class,
            $expression = QueryContinuation::of(Str::of('{&foo}'))->match(
                static fn($expression) => $expression,
                static fn() => null,
            ),
        );
        $this->assertSame('{&foo}', $expression->toString());
        $this->assertInstanceOf(
            QueryContinuation::class,
            $expression = QueryContinuation::of(Str::of('{&foo*}'))->match(
                static fn($expression) => $expression,
                static fn() => null,
            ),
        );
        $this->assertSame('{&foo*}', $expression->toString());
        $this->assertInstanceOf(
            QueryContinuation::class,
            $expression = QueryContinuation::of(Str::of('{&foo:42}'))->match(
                static fn($expression) => $expression,
                static fn() => null,
            ),
        );
        $this->assertSame('{&foo:42}', $expression->toString());
    }

    public function testReturnNothingWhenInvalidPattern()
    {
        $this->assertNull(QueryContinuation::of(Str::of('{foo}'))->match(
            static fn($expression) => $expression,
            static fn() => null,
        ));
    }

    public function testThrowExplodeRegex()
    {
        $this->expectException(LogicException::class);

        QueryContinuation::of(Str::of('{&foo*}'))->match(
            static fn($expression) => $expression->regex()->unwrap(),
            static fn() => null,
        );
    }

    public function testRegex()
    {
        $this->assertSame(
            '\&foo=(?<foo>[a-zA-Z0-9\%\-\.\_\~]*)',
            QueryContinuation::of(Str::of('{&foo}'))->match(
                static fn($expression) => $expression->regex()->unwrap(),
                static fn() => null,
            ),
        );
        $this->assertSame(
            '\&foo=(?<foo>[a-zA-Z0-9\%\-\.\_\~]{2})',
            QueryContinuation::of(Str::of('{&foo:2}'))->match(
                static fn($expression) => $expression->regex()->unwrap(),
                static fn() => null,
            ),
        );
    }
}
