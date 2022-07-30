<?php
declare(strict_types = 1);

namespace Tests\Innmind\UrlTemplate\Expression\Level4;

use Innmind\UrlTemplate\{
    Expression\Level4\Fragment,
    Expression,
    Exception\DomainException,
    Exception\LogicException,
};
use Innmind\Immutable\{
    Map,
    Str,
};
use PHPUnit\Framework\TestCase;
use Innmind\BlackBox\{
    PHPUnit\BlackBox,
    Set,
};

class FragmentTest extends TestCase
{
    use BlackBox;

    public function testInterface()
    {
        $this->assertInstanceOf(
            Expression::class,
            Fragment::of(Str::of('{#foo}')),
        );
        $this->assertInstanceOf(
            Expression::class,
            Fragment::of(Str::of('{#foo*}')),
        );
        $this->assertInstanceOf(
            Expression::class,
            Fragment::of(Str::of('{#foo:42}')),
        );
    }

    public function testStringCast()
    {
        $this->assertSame('{#foo}', Fragment::of(Str::of('{#foo}'))->toString());
        $this->assertSame('{#foo*}', Fragment::of(Str::of('{#foo*}'))->toString());
        $this->assertSame('{#foo:42}', Fragment::of(Str::of('{#foo:42}'))->toString());
    }

    public function testThrowWhenNegativeLimit()
    {
        $this
            ->forAll(Set\Integers::below(1))
            ->then(function(int $int): void {
                $this->expectException(DomainException::class);

                Fragment::of(Str::of("{#list:$int}"));
            });
    }

    public function testExpand()
    {
        $variables = Map::of()
            ('var', 'value')
            ('hello', 'Hello World!')
            ('path', '/foo/bar')
            ('list', ['red', 'green', 'blue'])
            ('keys', [['semi', ';'], ['dot', '.'], ['comma', ',']]);

        $this->assertSame(
            '#/foo/b',
            Fragment::of(Str::of('{#path:6}'))->expand($variables),
        );
        $this->assertSame(
            '#red,green,blue',
            Fragment::of(Str::of('{#list}'))->expand($variables),
        );
        $this->assertSame(
            '#red,green,blue',
            Fragment::of(Str::of('{#list*}'))->expand($variables),
        );
        $this->assertSame(
            '#semi,;,dot,.,comma,,',
            Fragment::of(Str::of('{#keys}'))->expand($variables),
        );
        $this->assertSame(
            '#semi=;,dot=.,comma=,',
            Fragment::of(Str::of('{#keys*}'))->expand($variables),
        );
    }

    public function testOf()
    {
        $this->assertInstanceOf(
            Fragment::class,
            $expression = Fragment::of(Str::of('{#foo}')),
        );
        $this->assertSame('{#foo}', $expression->toString());
        $this->assertInstanceOf(
            Fragment::class,
            $expression = Fragment::of(Str::of('{#foo*}')),
        );
        $this->assertSame('{#foo*}', $expression->toString());
        $this->assertInstanceOf(
            Fragment::class,
            $expression = Fragment::of(Str::of('{#foo:42}')),
        );
        $this->assertSame('{#foo:42}', $expression->toString());
    }

    public function testThrowWhenInvalidPattern()
    {
        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('{foo}');

        Fragment::of(Str::of('{foo}'));
    }

    public function testThrowExplodeRegex()
    {
        $this->expectException(LogicException::class);

        Fragment::of(Str::of('{#foo*}'))->regex();
    }

    public function testRegex()
    {
        $this->assertSame(
            '\#(?<foo>[a-zA-Z0-9\%:/\?#\[\]@!$&\'\(\)\*\+,;=\-\.\_\~]*)',
            Fragment::of(Str::of('{#foo}'))->regex(),
        );
        $this->assertSame(
            '\#(?<foo>[a-zA-Z0-9\%:/\?#\[\]@!$&\'\(\)\*\+,;=\-\.\_\~]{2})',
            Fragment::of(Str::of('{#foo:2}'))->regex(),
        );
    }
}
