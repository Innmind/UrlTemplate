<?php
declare(strict_types = 1);

namespace Tests\Innmind\UrlTemplate\Expression\Level4;

use Innmind\UrlTemplate\{
    Expression\Level4\Fragment,
    Expression\Name,
    Expression,
    Exception\DomainException,
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

class FragmentTest extends TestCase
{
    use TestTrait;

    public function testInterface()
    {
        $this->assertInstanceOf(
            Expression::class,
            new Fragment(new Name('foo'))
        );
        $this->assertInstanceOf(
            Expression::class,
            Fragment::explode(new Name('foo'))
        );
        $this->assertInstanceOf(
            Expression::class,
            Fragment::limit(new Name('foo'), 42)
        );
    }

    public function testStringCast()
    {
        $this->assertSame('{#foo}', (string) new Fragment(new Name('foo')));
        $this->assertSame('{#foo*}', (string) Fragment::explode(new Name('foo')));
        $this->assertSame('{#foo:42}', (string) Fragment::limit(new Name('foo'), 42));
    }

    public function testThrowWhenNegativeLimit()
    {
        $this
            ->forAll(Generator\neg())
            ->then(function(int $int): void {
                $this->expectException(DomainException::class);

                Fragment::limit(new Name('foo'), $int);
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
            '#/foo/b',
            Fragment::limit(new Name('path'), 6)->expand($variables)
        );
        $this->assertSame(
            '#red,green,blue',
            (new Fragment(new Name('list')))->expand($variables)
        );
        $this->assertSame(
            '#red,green,blue',
            Fragment::explode(new Name('list'))->expand($variables)
        );
        $this->assertSame(
            '#semi,;,dot,.,comma,,',
            (new Fragment(new Name('keys')))->expand($variables)
        );
        $this->assertSame(
            '#semi=;,dot=.,comma=,',
            Fragment::explode(new Name('keys'))->expand($variables)
        );
    }

    public function testOf()
    {
        $this->assertInstanceOf(
            Fragment::class,
            $expression = Fragment::of(Str::of('{#foo}'))
        );
        $this->assertSame('{#foo}', (string) $expression);
        $this->assertInstanceOf(
            Fragment::class,
            $expression = Fragment::of(Str::of('{#foo*}'))
        );
        $this->assertSame('{#foo*}', (string) $expression);
        $this->assertInstanceOf(
            Fragment::class,
            $expression = Fragment::of(Str::of('{#foo:42}'))
        );
        $this->assertSame('{#foo:42}', (string) $expression);
    }

    public function testThrowWhenInvalidPattern()
    {
        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('{foo}');

        Fragment::of(Str::of('{foo}'));
    }
}
