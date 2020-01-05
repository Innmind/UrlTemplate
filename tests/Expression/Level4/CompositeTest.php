<?php
declare(strict_types = 1);

namespace Tests\Innmind\UrlTemplate\Expression\Level4;

use Innmind\UrlTemplate\{
    Expression\Level4\Composite,
    Expression\Level4\Path,
    Expression\Level4,
    Expression\Name,
    Expression,
    Exception\DomainException,
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
                $this->createMock(Expression::class)
            )
        );
    }

    public function testStringCast()
    {
        $this->assertSame(
            '{/var:1,var}',
            (new Composite(
                '/',
                Path::limit(new Name('var'), 1),
                new Level4(new Name('var'))
            ))->toString()
        );
        $this->assertSame(
            '{/list*,path:4}',
            (new Composite(
                '/',
                Path::explode(new Name('list')),
                Level4::limit(new Name('path'), 4)
            ))->toString()
        );
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
            '/v/value',
            (new Composite(
                '/',
                Path::limit(new Name('var'), 1),
                new Level4(new Name('var'))
            ))->expand($variables)
        );
        $this->assertSame(
            '/red/green/blue/%2Ffoo',
            (new Composite(
                '/',
                Path::explode(new Name('list')),
                Level4::limit(new Name('path'), 4)
            ))->expand($variables)
        );
    }

    /**
     * @dataProvider cases
     */
    public function testOf($pattern, $expected)
    {
        $variables = Map::of('string', 'variable')
            ('var', 'value')
            ('hello', 'Hello World!')
            ('path', '/foo/bar')
            ('list', ['red', 'green', 'blue'])
            ('keys', [['semi', ';'], ['dot', '.'], ['comma', ',']]);

        $expression = Composite::of(Str::of($pattern));

        $this->assertSame($pattern, $expression->toString());
        $this->assertSame($expected, $expression->expand($variables));
    }

    public function testThrowWhenInvalidPattern()
    {
        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('foo');

        Composite::of(Str::of('foo'));
    }

    public function testRegex()
    {
        $this->assertSame(
            '(?<var>[a-zA-Z0-9\%\-\.\_\~]*)\,(?<hello>[a-zA-Z0-9\%\-\.\_\~]*)',
            Composite::of(Str::of('{var,hello}'))->regex()
        );
        $this->assertSame(
            '(?<var>[a-zA-Z0-9\%\-\.\_\~]*)\,(?<hello>[a-zA-Z0-9\%\-\.\_\~]{5})',
            Composite::of(Str::of('{var,hello:5}'))->regex()
        );
        $this->assertSame(
            '(?<var>[a-zA-Z0-9\%:/\?#\[\]@!$&\'\(\)\*\+,;=\-\.\_\~]*)\,(?<hello>[a-zA-Z0-9\%:/\?#\[\]@!$&\'\(\)\*\+,;=\-\.\_\~]{5})',
            Composite::of(Str::of('{+var,hello:5}'))->regex()
        );
        $this->assertSame(
            '\#(?<var>[a-zA-Z0-9\%:/\?#\[\]@!$&\'\(\)\*\+,;=\-\.\_\~]*)\,(?<hello>[a-zA-Z0-9\%:/\?#\[\]@!$&\'\(\)\*\+,;=\-\.\_\~]{5})',
            Composite::of(Str::of('{#var,hello:5}'))->regex()
        );
        $this->assertSame(
            '\.(?<var>[a-zA-Z0-9\%\-\.\_\~]*)\.(?<hello>[a-zA-Z0-9\%\-\.\_\~]{5})',
            Composite::of(Str::of('{.var,hello:5}'))->regex()
        );
        $this->assertSame(
            '\/(?<var>[a-zA-Z0-9\%\-\.\_\~]*)\/(?<hello>[a-zA-Z0-9\%\-\.\_\~]{5})',
            Composite::of(Str::of('{/var,hello:5}'))->regex()
        );
        $this->assertSame(
            '\;var=(?<var>[a-zA-Z0-9\%\-\.\_\~]*)\;hello=(?<hello>[a-zA-Z0-9\%\-\.\_\~]{5})',
            Composite::of(Str::of('{;var,hello:5}'))->regex()
        );
        $this->assertSame(
            '\?var=(?<var>[a-zA-Z0-9\%\-\.\_\~]*)\&hello=(?<hello>[a-zA-Z0-9\%\-\.\_\~]{5})',
            Composite::of(Str::of('{?var,hello:5}'))->regex()
        );
        $this->assertSame(
            '\&var=(?<var>[a-zA-Z0-9\%\-\.\_\~]*)\&hello=(?<hello>[a-zA-Z0-9\%\-\.\_\~]{5})',
            Composite::of(Str::of('{&var,hello:5}'))->regex()
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
