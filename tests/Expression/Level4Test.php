<?php
declare(strict_types = 1);

namespace Tests\Innmind\UrlTemplate\Expression;

use Innmind\UrlTemplate\{
    Expression\Level4,
    Expression\Name,
    Expression,
    Exception\DomainException,
};
use Innmind\Immutable\Map;
use PHPUnit\Framework\TestCase;
use Eris\{
    Generator,
    TestTrait,
};

class Level4Test extends TestCase
{
    use TestTrait;

    public function testInterface()
    {
        $this->assertInstanceOf(
            Expression::class,
            new Level4(new Name('foo'))
        );
        $this->assertInstanceOf(
            Expression::class,
            Level4::explode(new Name('foo'))
        );
        $this->assertInstanceOf(
            Expression::class,
            Level4::limit(new Name('foo'), 42)
        );
    }

    public function testStringCast()
    {
        $this->assertSame('{foo}', (string) new Level4(new Name('foo')));
        $this->assertSame('{foo*}', (string) Level4::explode(new Name('foo')));
        $this->assertSame('{foo:42}', (string) Level4::limit(new Name('foo'), 42));
    }

    public function testThrowWhenNegativeLimit()
    {
        $this
            ->forAll(Generator\neg())
            ->then(function(int $int): void {
                $this->expectException(DomainException::class);

                Level4::limit(new Name('foo'), $int);
            });
    }

    public function testExpand()
    {
        $variables = (new Map('string', 'variable'))
            ->put('var', 'value')
            ->put('hello', 'Hello World!')
            ->put('path', '/foo/bar')
            ->put('list', ['red', 'green', 'blue'])
            ->put('keys', [['semi', ';'], ['dot', '.'], ['comma', ',']]);

        $this->assertSame(
            'val',
            Level4::limit(new Name('var'), 3)->expand($variables)
        );
        $this->assertSame(
            'value',
            Level4::limit(new Name('var'), 30)->expand($variables)
        );
        $this->assertSame(
            'red,green,blue',
            (new Level4(new Name('list')))->expand($variables)
        );
        $this->assertSame(
            'red,green,blue',
            Level4::explode(new Name('list'))->expand($variables)
        );
        $this->assertSame(
            'semi=%3B,dot=.,comma=%2C',
            (new Level4(new Name('keys')))->expand($variables)
        );
        $this->assertSame(
            'semi=%3B,dot=.,comma=%2C',
            Level4::explode(new Name('keys'))->expand($variables)
        );
    }
}
