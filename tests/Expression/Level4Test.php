<?php
declare(strict_types = 1);

namespace Tests\Innmind\UrlTemplate\Expression;

use Innmind\UrlTemplate\{
    Expression\Level4,
    Expression,
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

class Level4Test extends TestCase
{
    use BlackBox;

    public function testInterface()
    {
        $this->assertInstanceOf(
            Expression::class,
            Level4::of(Str::of('{foo}'))->match(
                static fn($expression) => $expression,
                static fn() => null,
            ),
        );
        $this->assertInstanceOf(
            Expression::class,
            Level4::of(Str::of('{foo*}'))->match(
                static fn($expression) => $expression,
                static fn() => null,
            ),
        );
        $this->assertInstanceOf(
            Expression::class,
            Level4::of(Str::of('{foo:42}'))->match(
                static fn($expression) => $expression,
                static fn() => null,
            ),
        );
    }

    public function testStringCast()
    {
        $this->assertSame(
            '{foo}',
            Level4::of(Str::of('{foo}'))->match(
                static fn($expression) => $expression->toString(),
                static fn() => null,
            ),
        );
        $this->assertSame(
            '{foo*}',
            Level4::of(Str::of('{foo*}'))->match(
                static fn($expression) => $expression->toString(),
                static fn() => null,
            ),
        );
        $this->assertSame(
            '{foo:42}',
            Level4::of(Str::of('{foo:42}'))->match(
                static fn($expression) => $expression->toString(),
                static fn() => null,
            ),
        );
    }

    public function testReturnNothingWhenNegativeLimit()
    {
        $this
            ->forAll(Set\Integers::below(1))
            ->then(function(int $int): void {
                $this->assertNull(Level4::of(Str::of("{foo:$int}"))->match(
                    static fn($expression) => $expression,
                    static fn() => null,
                ));
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
            'val',
            Level4::of(Str::of('{var:3}'))->match(
                static fn($expression) => $expression->expand($variables),
                static fn() => null,
            ),
        );
        $this->assertSame(
            'value',
            Level4::of(Str::of('{var:30}'))->match(
                static fn($expression) => $expression->expand($variables),
                static fn() => null,
            ),
        );
        $this->assertSame(
            '%2Ffoo',
            Level4::of(Str::of('{path:4}'))->match(
                static fn($expression) => $expression->expand($variables),
                static fn() => null,
            ),
        );
        $this->assertSame(
            'red,green,blue',
            Level4::of(Str::of('{list}'))->match(
                static fn($expression) => $expression->expand($variables),
                static fn() => null,
            ),
        );
        $this->assertSame(
            'red,green,blue',
            Level4::of(Str::of('{list*}'))->match(
                static fn($expression) => $expression->expand($variables),
                static fn() => null,
            ),
        );
        $this->assertSame(
            'semi,%3B,dot,.,comma,%2C',
            Level4::of(Str::of('{keys}'))->match(
                static fn($expression) => $expression->expand($variables),
                static fn() => null,
            ),
        );
        $this->assertSame(
            'semi=%3B,dot=.,comma=%2C',
            Level4::of(Str::of('{keys*}'))->match(
                static fn($expression) => $expression->expand($variables),
                static fn() => null,
            ),
        );
    }

    public function testOf()
    {
        $this->assertInstanceOf(
            Level4::class,
            $expression = Level4::of(Str::of('{foo}'))->match(
                static fn($expression) => $expression,
                static fn() => null,
            ),
        );
        $this->assertSame('{foo}', $expression->toString());
        $this->assertInstanceOf(
            Level4::class,
            $expression = Level4::of(Str::of('{foo*}'))->match(
                static fn($expression) => $expression,
                static fn() => null,
            ),
        );
        $this->assertSame('{foo*}', $expression->toString());
        $this->assertInstanceOf(
            Level4::class,
            $expression = Level4::of(Str::of('{foo:42}'))->match(
                static fn($expression) => $expression,
                static fn() => null,
            ),
        );
        $this->assertSame('{foo:42}', $expression->toString());
    }

    public function testReturnNothingWhenInvalidPattern()
    {
        $this->assertNull(Level4::of(Str::of('foo'))->match(
            static fn($expression) => $expression,
            static fn() => null,
        ));
    }

    public function testThrowExplodeRegex()
    {
        $this->expectException(LogicException::class);

        Level4::of(Str::of('{foo*}'))->match(
            static fn($expression) => $expression->regex(),
            static fn() => null,
        );
    }

    public function testRegex()
    {
        $this->assertSame(
            '(?<foo>[a-zA-Z0-9\%\-\.\_\~]*)',
            Level4::of(Str::of('{foo}'))->match(
                static fn($expression) => $expression->regex(),
                static fn() => null,
            ),
        );
        $this->assertSame(
            '(?<foo>[a-zA-Z0-9\%\-\.\_\~]{2})',
            Level4::of(Str::of('{foo:2}'))->match(
                static fn($expression) => $expression->regex(),
                static fn() => null,
            ),
        );
    }
}
