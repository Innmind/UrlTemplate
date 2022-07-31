<?php
declare(strict_types = 1);

namespace Tests\Innmind\UrlTemplate\Expression\Level3;

use Innmind\UrlTemplate\{
    Expression\Level3\Parameters,
    Expression,
};
use Innmind\Immutable\{
    Map,
    Str,
};
use PHPUnit\Framework\TestCase;

class ParametersTest extends TestCase
{
    public function testInterface()
    {
        $this->assertInstanceOf(
            Expression::class,
            Parameters::of(Str::of('{;foo,bar}'))->match(
                static fn($expression) => $expression,
                static fn() => null,
            ),
        );
    }

    public function testStringCast()
    {
        $this->assertSame(
            '{;foo,bar}',
            Parameters::of(Str::of('{;foo,bar}'))->match(
                static fn($expression) => $expression->toString(),
                static fn() => null,
            ),
        );
    }

    public function testExpand()
    {
        $variables = Map::of()
            ('var', 'value')
            ('hello', 'Hello World!')
            ('empty', '')
            ('path', '/foo/bar')
            ('x', '1024')
            ('y', '768');

        $this->assertSame(
            ';x=1024;y=768',
            Parameters::of(Str::of('{;x,y}'))->match(
                static fn($expression) => $expression->expand($variables),
                static fn() => null,
            ),
        );
        $this->assertSame(
            ';x=1024;y=768;empty',
            Parameters::of(Str::of('{;x,y,empty}'))->match(
                static fn($expression) => $expression->expand($variables),
                static fn() => null,
            ),
        );
    }

    public function testOf()
    {
        $this->assertInstanceOf(
            Parameters::class,
            $expression = Parameters::of(Str::of('{;foo,bar}'))->match(
                static fn($expression) => $expression,
                static fn() => null,
            ),
        );
        $this->assertSame('{;foo,bar}', $expression->toString());
    }

    public function testReturnNothingWhenInvalidPattern()
    {
        $this->assertNull(Parameters::of(Str::of('{foo}'))->match(
            static fn($expression) => $expression,
            static fn() => null,
        ));
    }

    public function testRegex()
    {
        $this->assertSame(
            '\;foo=?(?<foo>[a-zA-Z0-9\%\-\.\_\~]*)\;bar=?(?<bar>[a-zA-Z0-9\%\-\.\_\~]*)',
            Parameters::of(Str::of('{;foo,bar}'))->match(
                static fn($expression) => $expression->regex(),
                static fn() => null,
            ),
        );
    }
}
