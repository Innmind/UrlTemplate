<?php
declare(strict_types = 1);

namespace Tests\Innmind\UrlTemplate\Expression\Level4;

use Innmind\UrlTemplate\{
    Expression\Level4\Label,
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

class LabelTest extends TestCase
{
    use TestTrait;

    public function testInterface()
    {
        $this->assertInstanceOf(
            Expression::class,
            new Label(new Name('foo'))
        );
        $this->assertInstanceOf(
            Expression::class,
            Label::explode(new Name('foo'))
        );
        $this->assertInstanceOf(
            Expression::class,
            Label::limit(new Name('foo'), 42)
        );
    }

    public function testStringCast()
    {
        $this->assertSame('{.foo}', (string) new Label(new Name('foo')));
        $this->assertSame('{.foo*}', (string) Label::explode(new Name('foo')));
        $this->assertSame('{.foo:42}', (string) Label::limit(new Name('foo'), 42));
    }

    public function testThrowWhenNegativeLimit()
    {
        $this
            ->forAll(Generator\neg())
            ->then(function(int $int): void {
                $this->expectException(DomainException::class);

                Label::limit(new Name('foo'), $int);
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
            '.val',
            Label::limit(new Name('var'), 3)->expand($variables)
        );
        $this->assertSame(
            '.red,green,blue',
            (new Label(new Name('list')))->expand($variables)
        );
        $this->assertSame(
            '.red.green.blue',
            Label::explode(new Name('list'))->expand($variables)
        );
        $this->assertSame(
            '.semi=%3B,dot=.,comma=%2C',
            (new Label(new Name('keys')))->expand($variables)
        );
        $this->assertSame(
            '.semi=%3B.dot=..comma=%2C',
            Label::explode(new Name('keys'))->expand($variables)
        );
    }
}
