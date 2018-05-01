<?php
declare(strict_types = 1);

namespace Tests\Innmind\UrlTemplate\Expression\Level3;

use Innmind\UrlTemplate\{
    Expression\Level3\Parameters,
    Expression\Name,
    Expression,
};
use Innmind\Immutable\Map;
use PHPUnit\Framework\TestCase;

class ParametersTest extends TestCase
{
    public function testInterface()
    {
        $this->assertInstanceOf(
            Expression::class,
            new Parameters(new Name('foo'), new Name('bar'))
        );
    }

    public function testStringCast()
    {
        $this->assertSame(
            '{;foo,bar}',
            (string) new Parameters(new Name('foo'), new Name('bar'))
        );
    }

    public function testExpand()
    {
        $variables = (new Map('string', 'variable'))
            ->put('var', 'value')
            ->put('hello', 'Hello World!')
            ->put('empty', '')
            ->put('path', '/foo/bar')
            ->put('x', '1024')
            ->put('y', '768');

        $this->assertSame(
            ';x=1024;y=768',
            (new Parameters(new Name('x'), new Name('y')))->expand($variables)
        );
        $this->assertSame(
            ';x=1024;y=768;empty',
            (new Parameters(new Name('x'), new Name('y'), new Name('empty')))->expand($variables)
        );
    }
}
