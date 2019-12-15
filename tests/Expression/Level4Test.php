<?php
declare(strict_types = 1);

namespace Tests\Innmind\UrlTemplate\Expression;

use Innmind\UrlTemplate\{
    Expression\Level4,
    Expression\Name,
    Expression,
    Exception\DomainException,
    Exception\LogicException,
};
use Innmind\Immutable\{
    Map,
    Str,
};
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
        $variables = Map::of('string', 'variable')
            ('var', 'value')
            ('hello', 'Hello World!')
            ('path', '/foo/bar')
            ('list', ['red', 'green', 'blue'])
            ('keys', [['semi', ';'], ['dot', '.'], ['comma', ',']]);

        $this->assertSame(
            'val',
            Level4::limit(new Name('var'), 3)->expand($variables)
        );
        $this->assertSame(
            'value',
            Level4::limit(new Name('var'), 30)->expand($variables)
        );
        $this->assertSame(
            '%2Ffoo',
            Level4::limit(new Name('path'), 4)->expand($variables)
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
            'semi,%3B,dot,.,comma,%2C',
            (new Level4(new Name('keys')))->expand($variables)
        );
        $this->assertSame(
            'semi=%3B,dot=.,comma=%2C',
            Level4::explode(new Name('keys'))->expand($variables)
        );
    }

    public function testOf()
    {
        $this->assertInstanceOf(
            Level4::class,
            $expression = Level4::of(Str::of('{foo}'))
        );
        $this->assertSame('{foo}', (string) $expression);
        $this->assertInstanceOf(
            Level4::class,
            $expression = Level4::of(Str::of('{foo*}'))
        );
        $this->assertSame('{foo*}', (string) $expression);
        $this->assertInstanceOf(
            Level4::class,
            $expression = Level4::of(Str::of('{foo:42}'))
        );
        $this->assertSame('{foo:42}', (string) $expression);
    }

    public function testThrowWhenInvalidPattern()
    {
        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('foo');

        Level4::of(Str::of('foo'));
    }

    public function testThrowExplodeRegex()
    {
        $this->expectException(LogicException::class);

        Level4::of(Str::of('{foo*}'))->regex();
    }

    public function testRegex()
    {
        $this->assertSame(
            '(?<foo>[a-zA-Z0-9\%\-\.\_\~]*)',
            Level4::of(Str::of('{foo}'))->regex()
        );
        $this->assertSame(
            '(?<foo>[a-zA-Z0-9\%\-\.\_\~]{2})',
            Level4::of(Str::of('{foo:2}'))->regex()
        );
    }
}
