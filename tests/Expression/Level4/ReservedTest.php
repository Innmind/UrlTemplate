<?php
declare(strict_types = 1);

namespace Tests\Innmind\UrlTemplate\Expression\Level4;

use Innmind\UrlTemplate\{
    Expression\Level4\Reserved,
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

class ReservedTest extends TestCase
{
    use TestTrait;

    public function testInterface()
    {
        $this->assertInstanceOf(
            Expression::class,
            new Reserved(new Name('foo'))
        );
        $this->assertInstanceOf(
            Expression::class,
            Reserved::explode(new Name('foo'))
        );
        $this->assertInstanceOf(
            Expression::class,
            Reserved::limit(new Name('foo'), 42)
        );
    }

    public function testStringCast()
    {
        $this->assertSame('{+foo}', (string) new Reserved(new Name('foo')));
        $this->assertSame('{+foo*}', (string) Reserved::explode(new Name('foo')));
        $this->assertSame('{+foo:42}', (string) Reserved::limit(new Name('foo'), 42));
    }

    public function testThrowWhenNegativeLimit()
    {
        $this
            ->forAll(Generator\neg())
            ->then(function(int $int): void {
                $this->expectException(DomainException::class);

                Reserved::limit(new Name('foo'), $int);
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
            '/foo/b',
            Reserved::limit(new Name('path'), 6)->expand($variables)
        );
        $this->assertSame(
            'red,green,blue',
            (new Reserved(new Name('list')))->expand($variables)
        );
        $this->assertSame(
            'red,green,blue',
            Reserved::explode(new Name('list'))->expand($variables)
        );
        $this->assertSame(
            'semi,;,dot,.,comma,,',
            (new Reserved(new Name('keys')))->expand($variables)
        );
        $this->assertSame(
            'semi=;,dot=.,comma=,',
            Reserved::explode(new Name('keys'))->expand($variables)
        );
    }

    public function testOf()
    {
        $this->assertInstanceOf(
            Reserved::class,
            $expression = Reserved::of(Str::of('{+foo}'))
        );
        $this->assertSame('{+foo}', (string) $expression);
        $this->assertInstanceOf(
            Reserved::class,
            $expression = Reserved::of(Str::of('{+foo*}'))
        );
        $this->assertSame('{+foo*}', (string) $expression);
        $this->assertInstanceOf(
            Reserved::class,
            $expression = Reserved::of(Str::of('{+foo:42}'))
        );
        $this->assertSame('{+foo:42}', (string) $expression);
    }

    public function testThrowWhenInvalidPattern()
    {
        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('{foo}');

        Reserved::of(Str::of('{foo}'));
    }

    public function testThrowExplodeRegex()
    {
        $this->expectException(LogicException::class);

        Reserved::of(Str::of('{+foo*}'))->regex();
    }

    public function testRegex()
    {
        $this->assertSame(
            '(?<foo>[a-zA-Z0-9\%:/\?#\[\]@!$&\'\(\)\*\+,;=\-\.\_\~]*)',
            Reserved::of(Str::of('{+foo}'))->regex()
        );
        $this->assertSame(
            '(?<foo>[a-zA-Z0-9\%:/\?#\[\]@!$&\'\(\)\*\+,;=\-\.\_\~]{2})',
            Reserved::of(Str::of('{+foo:2}'))->regex()
        );
    }
}
