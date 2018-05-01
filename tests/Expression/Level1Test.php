<?php
declare(strict_types = 1);

namespace Tests\Innmind\UrlTemplate\Expression;

use Innmind\UrlTemplate\{
    Expression\Level1,
    Expression\Name,
    Expression,
};
use Innmind\Immutable\Map;
use PHPUnit\Framework\TestCase;

class Level1Test extends TestCase
{
    public function testInterface()
    {
        $this->assertInstanceOf(
            Expression::class,
            new Level1(new Name('foo'))
        );
    }

    public function testStringCast()
    {
        $this->assertSame('{foo}', (string) new Level1(new Name('foo')));
    }

    public function testExpand()
    {
        $expression = new Level1(new Name('foo'));

        $this->assertSame('value', $expression->expand(
            (new Map('string', 'variable'))->put('foo', 'value')
        ));
        $this->assertSame('Hello%20World%21', $expression->expand(
            (new Map('string', 'variable'))->put('foo', 'Hello World!')
        ));
        $this->assertSame('', $expression->expand(
            new Map('string', 'variable')
        ));
    }
}
