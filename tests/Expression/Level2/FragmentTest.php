<?php
declare(strict_types = 1);

namespace Tests\Innmind\UrlTemplate\Expression\Level2;

use Innmind\UrlTemplate\{
    Expression\Level2\Fragment,
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
            new Fragment(new Name('foo'))
        );
    }

    public function testStringCast()
    {
        $this->assertSame('{#foo}', (string) new Fragment(new Name('foo')));
    }

    public function testExpand()
    {
        $expression = new Fragment(new Name('foo'));

        $this->assertSame('#value', $expression->expand(
            Map::of('string', 'variable')('foo', 'value')
        ));
        $this->assertSame('#Hello%20World!', $expression->expand(
            Map::of('string', 'variable')('foo', 'Hello World!')
        ));
        $this->assertSame('#/foo/bar', $expression->expand(
            Map::of('string', 'variable')('foo', '/foo/bar')
        ));
        $this->assertSame('', $expression->expand(
            Map::of('string', 'variable')
        ));
    }

    public function testOf()
    {
        $this->assertInstanceOf(
            Fragment::class,
            $expression = Fragment::of(Str::of('{#foo}'))
        );
        $this->assertSame('{#foo}', (string) $expression);
    }

    public function testThrowWhenInvalidPattern()
    {
        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('foo');

        Fragment::of(Str::of('foo'));
    }

    public function testRegex()
    {
        $this->assertSame(
            '\#(?<foo>[a-zA-Z0-9\%:/\?#\[\]@!$&\'\(\)\*\+,;=\-\.\_\~]*)',
            Fragment::of(Str::of('{#foo}'))->regex()
        );
    }
}
