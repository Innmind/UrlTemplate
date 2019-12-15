<?php
declare(strict_types = 1);

namespace Tests\Innmind\UrlTemplate\Expression\Level3;

use Innmind\UrlTemplate\{
    Expression\Level3\Fragment,
    Expression\Name,
    Expression,
    Exception\DomainException,
};
use Innmind\Immutable\{
    Map,
    Str,
};
use PHPUnit\Framework\TestCase;

class FragmentTest extends TestCase
{
    public function testInterface()
    {
        $this->assertInstanceOf(
            Expression::class,
            new Fragment(new Name('foo'), new Name('bar'))
        );
    }

    public function testStringCast()
    {
        $this->assertSame(
            '{#foo,bar}',
            (string) new Fragment(new Name('foo'), new Name('bar'))
        );
    }

    public function testExpand()
    {
        $variables = Map::of('string', 'variable')
            ('var', 'value')
            ('hello', 'Hello World!')
            ('empty', '')
            ('path', '/foo/bar')
            ('x', '1024')
            ('y', '768');

        $this->assertSame(
            '#1024,Hello%20World!,768',
            (new Fragment(new Name('x'), new Name('hello'), new Name('y')))->expand($variables)
        );
        $this->assertSame(
            '#/foo/bar,1024',
            (new Fragment(new Name('path'), new Name('x')))->expand($variables)
        );
    }

    public function testOf()
    {
        $this->assertInstanceOf(
            Fragment::class,
            $expression = Fragment::of(Str::of('{#foo,bar}'))
        );
        $this->assertSame('{#foo,bar}', (string) $expression);
    }

    public function testThrowWhenInvalidPattern()
    {
        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('{#foo}');

        Fragment::of(Str::of('{#foo}'));
    }

    public function testRegex()
    {
        $this->assertSame(
            '\#(?<foo>[a-zA-Z0-9\%:/\?#\[\]@!$&\'\(\)\*\+,;=\-\.\_\~]*),(?<bar>[a-zA-Z0-9\%:/\?#\[\]@!$&\'\(\)\*\+,;=\-\.\_\~]*)',
            Fragment::of(Str::of('{#foo,bar}'))->regex()
        );
    }
}
