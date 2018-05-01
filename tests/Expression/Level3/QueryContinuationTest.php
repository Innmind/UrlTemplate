<?php
declare(strict_types = 1);

namespace Tests\Innmind\UrlTemplate\Expression\Level3;

use Innmind\UrlTemplate\{
    Expression\Level3\QueryContinuation,
    Expression\Name,
    Expression,
};
use Innmind\Immutable\Map;
use PHPUnit\Framework\TestCase;

class QueryContinuationTest extends TestCase
{
    public function testInterface()
    {
        $this->assertInstanceOf(
            Expression::class,
            new QueryContinuation(new Name('foo'), new Name('bar'))
        );
    }

    public function testStringCast()
    {
        $this->assertSame(
            '{&foo,bar}',
            (string) new QueryContinuation(new Name('foo'), new Name('bar'))
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
            '&x=1024&y=768',
            (new QueryContinuation(new Name('x'), new Name('y')))->expand($variables)
        );
        $this->assertSame(
            '&x=1024&y=768&empty=',
            (new QueryContinuation(new Name('x'), new Name('y'), new Name('empty')))->expand($variables)
        );
    }
}