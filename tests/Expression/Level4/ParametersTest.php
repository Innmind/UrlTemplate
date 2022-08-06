<?php
declare(strict_types = 1);

namespace Tests\Innmind\UrlTemplate\Expression\Level4;

use Innmind\UrlTemplate\{
    Expression\Level4\Parameters,
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

class ParametersTest extends TestCase
{
    use BlackBox;

    public function testInterface()
    {
        $this->assertInstanceOf(
            Expression::class,
            Parameters::of(Str::of('{;foo}'))->match(
                static fn($expression) => $expression,
                static fn() => null,
            ),
        );
        $this->assertInstanceOf(
            Expression::class,
            Parameters::of(Str::of('{;foo*}'))->match(
                static fn($expression) => $expression,
                static fn() => null,
            ),
        );
        $this->assertInstanceOf(
            Expression::class,
            Parameters::of(Str::of('{;foo:42}'))->match(
                static fn($expression) => $expression,
                static fn() => null,
            ),
        );
    }

    public function testStringCast()
    {
        $this->assertSame(
            '{;foo}',
            Parameters::of(Str::of('{;foo}'))->match(
                static fn($expression) => $expression->toString(),
                static fn() => null,
            ),
        );
        $this->assertSame(
            '{;foo*}',
            Parameters::of(Str::of('{;foo*}'))->match(
                static fn($expression) => $expression->toString(),
                static fn() => null,
            ),
        );
        $this->assertSame(
            '{;foo:42}',
            Parameters::of(Str::of('{;foo:42}'))->match(
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
                $this->assertNull(Parameters::of(Str::of("{;foo:$int}"))->match(
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
            ';hello=Hello',
            Parameters::of(Str::of('{;hello:5}'))->match(
                static fn($expression) => $expression->expand($variables),
                static fn() => null,
            ),
        );
        $this->assertSame(
            ';list=red,green,blue',
            Parameters::of(Str::of('{;list}'))->match(
                static fn($expression) => $expression->expand($variables),
                static fn() => null,
            ),
        );
        $this->assertSame(
            ';list=red;list=green;list=blue',
            Parameters::of(Str::of('{;list*}'))->match(
                static fn($expression) => $expression->expand($variables),
                static fn() => null,
            ),
        );
        $this->assertSame(
            ';keys=semi,%3B,dot,.,comma,%2C',
            Parameters::of(Str::of('{;keys}'))->match(
                static fn($expression) => $expression->expand($variables),
                static fn() => null,
            ),
        );
        $this->assertSame(
            ';semi=%3B;dot=.;comma=%2C',
            Parameters::of(Str::of('{;keys*}'))->match(
                static fn($expression) => $expression->expand($variables),
                static fn() => null,
            ),
        );
    }

    public function testOf()
    {
        $this->assertInstanceOf(
            Parameters::class,
            $expression = Parameters::of(Str::of('{;foo}'))->match(
                static fn($expression) => $expression,
                static fn() => null,
            ),
        );
        $this->assertSame('{;foo}', $expression->toString());
        $this->assertInstanceOf(
            Parameters::class,
            $expression = Parameters::of(Str::of('{;foo*}'))->match(
                static fn($expression) => $expression,
                static fn() => null,
            ),
        );
        $this->assertSame('{;foo*}', $expression->toString());
        $this->assertInstanceOf(
            Parameters::class,
            $expression = Parameters::of(Str::of('{;foo:42}'))->match(
                static fn($expression) => $expression,
                static fn() => null,
            ),
        );
        $this->assertSame('{;foo:42}', $expression->toString());
    }

    public function testReturnNothingWhenInvalidPattern()
    {
        $this->assertNull(Parameters::of(Str::of('{foo}'))->match(
            static fn($expression) => $expression,
            static fn() => null,
        ));
    }

    public function testThrowExplodeRegex()
    {
        $this->expectException(LogicException::class);

        Parameters::of(Str::of('{;foo*}'))->match(
            static fn($expression) => $expression->regex(),
            static fn() => null,
        );
    }

    public function testRegex()
    {
        $this->assertSame(
            '\;foo=(?<foo>[a-zA-Z0-9\%\-\.\_\~]*)',
            Parameters::of(Str::of('{;foo}'))->match(
                static fn($expression) => $expression->regex(),
                static fn() => null,
            ),
        );
        $this->assertSame(
            '\;foo=(?<foo>[a-zA-Z0-9\%\-\.\_\~]{2})',
            Parameters::of(Str::of('{;foo:2}'))->match(
                static fn($expression) => $expression->regex(),
                static fn() => null,
            ),
        );
    }
}
