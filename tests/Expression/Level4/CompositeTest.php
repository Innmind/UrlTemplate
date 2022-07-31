<?php
declare(strict_types = 1);

namespace Tests\Innmind\UrlTemplate\Expression\Level4;

use Innmind\UrlTemplate\{
    Expression\Level4\Composite,
    Expression\Level4\Path,
    Expression\Level4,
    Expression,
};
use Innmind\Immutable\{
    Map,
    Str,
};
use PHPUnit\Framework\TestCase;

class CompositeTest extends TestCase
{
    public function testInterface()
    {
        $this->assertInstanceOf(
            Expression::class,
            new Composite(
                '/',
                $this->createMock(Expression::class),
            ),
        );
    }

    public function testStringCast()
    {
        $this->assertSame(
            '{/var:1,var}',
            (new Composite(
                '/',
                Path::of(Str::of('{/var:1}'))->match(
                    static fn($expression) => $expression,
                    static fn() => null,
                ),
                Level4::of(Str::of('{var}'))->match(
                    static fn($expression) => $expression,
                    static fn() => null,
                ),
            ))->toString(),
        );
        $this->assertSame(
            '{/list*,path:4}',
            (new Composite(
                '/',
                Path::of(Str::of('{/list*}'))->match(
                    static fn($expression) => $expression,
                    static fn() => null,
                ),
                Level4::of(Str::of('{path:4}'))->match(
                    static fn($expression) => $expression,
                    static fn() => null,
                ),
            ))->toString(),
        );
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
            '/v/value',
            (new Composite(
                '/',
                Path::of(Str::of('{/var:1}'))->match(
                    static fn($expression) => $expression,
                    static fn() => null,
                ),
                Level4::of(Str::of('{var}'))->match(
                    static fn($expression) => $expression,
                    static fn() => null,
                ),
            ))->expand($variables),
        );
        $this->assertSame(
            '/red/green/blue/%2Ffoo',
            (new Composite(
                '/',
                Path::of(Str::of('{/list*}'))->match(
                    static fn($expression) => $expression,
                    static fn() => null,
                ),
                Level4::of(Str::of('{path:4}'))->match(
                    static fn($expression) => $expression,
                    static fn() => null,
                ),
            ))->expand($variables),
        );
    }

    /**
     * @dataProvider cases
     */
    public function testOf($pattern, $expected)
    {
        $variables = Map::of()
            ('var', 'value')
            ('hello', 'Hello World!')
            ('path', '/foo/bar')
            ('list', ['red', 'green', 'blue'])
            ('keys', [['semi', ';'], ['dot', '.'], ['comma', ',']]);

        $expression = Composite::of(Str::of($pattern))->match(
            static fn($expression) => $expression,
            static fn() => null,
        );

        $this->assertSame($pattern, $expression->toString());
        $this->assertSame($expected, $expression->expand($variables));
    }

    public function testReturnNothingWhenInvalidPattern()
    {
        $this->assertNull(Composite::of(Str::of('foo'))->match(
            static fn($expression) => $expression,
            static fn() => null,
        ));
    }

    public function testRegex()
    {
        $this->assertSame(
            '(?<var>[a-zA-Z0-9\%\-\.\_\~]*)\,(?<hello>[a-zA-Z0-9\%\-\.\_\~]*)',
            Composite::of(Str::of('{var,hello}'))->match(
                static fn($expression) => $expression->regex(),
                static fn() => null,
            ),
        );
        $this->assertSame(
            '(?<var>[a-zA-Z0-9\%\-\.\_\~]*)\,(?<hello>[a-zA-Z0-9\%\-\.\_\~]{5})',
            Composite::of(Str::of('{var,hello:5}'))->match(
                static fn($expression) => $expression->regex(),
                static fn() => null,
            ),
        );
        $this->assertSame(
            '(?<var>[a-zA-Z0-9\%:/\?#\[\]@!$&\'\(\)\*\+,;=\-\.\_\~]*)\,(?<hello>[a-zA-Z0-9\%:/\?#\[\]@!$&\'\(\)\*\+,;=\-\.\_\~]{5})',
            Composite::of(Str::of('{+var,hello:5}'))->match(
                static fn($expression) => $expression->regex(),
                static fn() => null,
            ),
        );
        $this->assertSame(
            '\#(?<var>[a-zA-Z0-9\%:/\?#\[\]@!$&\'\(\)\*\+,;=\-\.\_\~]*)\,(?<hello>[a-zA-Z0-9\%:/\?#\[\]@!$&\'\(\)\*\+,;=\-\.\_\~]{5})',
            Composite::of(Str::of('{#var,hello:5}'))->match(
                static fn($expression) => $expression->regex(),
                static fn() => null,
            ),
        );
        $this->assertSame(
            '\.(?<var>[a-zA-Z0-9\%\-\.\_\~]*)\.(?<hello>[a-zA-Z0-9\%\-\.\_\~]{5})',
            Composite::of(Str::of('{.var,hello:5}'))->match(
                static fn($expression) => $expression->regex(),
                static fn() => null,
            ),
        );
        $this->assertSame(
            '\/(?<var>[a-zA-Z0-9\%\-\.\_\~]*)\/(?<hello>[a-zA-Z0-9\%\-\.\_\~]{5})',
            Composite::of(Str::of('{/var,hello:5}'))->match(
                static fn($expression) => $expression->regex(),
                static fn() => null,
            ),
        );
        $this->assertSame(
            '\;var=(?<var>[a-zA-Z0-9\%\-\.\_\~]*)\;hello=(?<hello>[a-zA-Z0-9\%\-\.\_\~]{5})',
            Composite::of(Str::of('{;var,hello:5}'))->match(
                static fn($expression) => $expression->regex(),
                static fn() => null,
            ),
        );
        $this->assertSame(
            '\?var=(?<var>[a-zA-Z0-9\%\-\.\_\~]*)\&hello=(?<hello>[a-zA-Z0-9\%\-\.\_\~]{5})',
            Composite::of(Str::of('{?var,hello:5}'))->match(
                static fn($expression) => $expression->regex(),
                static fn() => null,
            ),
        );
        $this->assertSame(
            '\&var=(?<var>[a-zA-Z0-9\%\-\.\_\~]*)\&hello=(?<hello>[a-zA-Z0-9\%\-\.\_\~]{5})',
            Composite::of(Str::of('{&var,hello:5}'))->match(
                static fn($expression) => $expression->regex(),
                static fn() => null,
            ),
        );
    }

    public function cases(): array
    {
        return [
            ['{var,hello}', 'value,Hello%20World%21'],
            ['{+hello,hello:5}', 'Hello%20World!,Hello'],
            ['{#hello,hello:5}', '#Hello%20World!,Hello'],
            ['{.hello,hello:5}', '.Hello%20World%21.Hello'],
            ['{/hello,hello:5}', '/Hello%20World%21/Hello'],
            ['{;hello,hello:5}', ';hello=Hello%20World%21;hello=Hello'],
            ['{?hello,hello:5}', '?hello=Hello%20World%21&hello=Hello'],
            ['{&hello,hello:5}', '&hello=Hello%20World%21&hello=Hello'],
        ];
    }
}
