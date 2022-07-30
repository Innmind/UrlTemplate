<?php
declare(strict_types = 1);

namespace Tests\Innmind\UrlTemplate\Expression\Level3;

use Innmind\UrlTemplate\{
    Expression\Level3\Fragment,
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
            Fragment::of(Str::of('{#foo,bar}')),
        );
    }

    public function testStringCast()
    {
        $this->assertSame(
            '{#foo,bar}',
            Fragment::of(Str::of('{#foo,bar}'))->toString(),
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
            '#1024,Hello%20World!,768',
            Fragment::of(Str::of('{#x,hello,y}'))->expand($variables),
        );
        $this->assertSame(
            '#/foo/bar,1024',
            Fragment::of(Str::of('{#path,x}'))->expand($variables),
        );
    }

    public function testOf()
    {
        $this->assertInstanceOf(
            Fragment::class,
            $expression = Fragment::of(Str::of('{#foo,bar}')),
        );
        $this->assertSame('{#foo,bar}', $expression->toString());
    }

    public function testThrowWhenInvalidPattern()
    {
        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('{foo}');

        Fragment::of(Str::of('{foo}'));
    }

    public function testRegex()
    {
        $this->assertSame(
            '\#(?<foo>[a-zA-Z0-9\%:/\?#\[\]@!$&\'\(\)\*\+,;=\-\.\_\~]*),(?<bar>[a-zA-Z0-9\%:/\?#\[\]@!$&\'\(\)\*\+,;=\-\.\_\~]*)',
            Fragment::of(Str::of('{#foo,bar}'))->regex(),
        );
    }
}
