<?php
declare(strict_types = 1);

namespace Tests\Innmind\UrlTemplate\Expression\Level2;

use Innmind\UrlTemplate\{
    Expression\Level2\Fragment,
    Expression\Name,
    Expression,
};
use Innmind\Immutable\Map;
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
            (new Map('string', 'variable'))->put('foo', 'value')
        ));
        $this->assertSame('#Hello%20World!', $expression->expand(
            (new Map('string', 'variable'))->put('foo', 'Hello World!')
        ));
        $this->assertSame('#/foo/bar', $expression->expand(
            (new Map('string', 'variable'))->put('foo', '/foo/bar')
        ));
        $this->assertSame('#', $expression->expand(
            new Map('string', 'variable')
        ));
    }
}
