<?php
declare(strict_types = 1);

namespace Tests\Innmind\UrlTemplate\Expression\Level4;

use Innmind\UrlTemplate\{
    Expression\Level4\Fragment,
    Expression\Name,
    Expression,
};
use Innmind\Immutable\{
    Map,
    Str,
};
use Innmind\BlackBox\{
    PHPUnit\BlackBox,
    PHPUnit\Framework\TestCase,
    Set,
};

class FragmentTest extends TestCase
{
    use BlackBox;

    public function testInterface()
    {
        $this->assertInstanceOf(
            Expression::class,
            Fragment::of(Str::of('{#foo}'))->match(
                static fn($expression) => $expression,
                static fn() => null,
            ),
        );
        $this->assertInstanceOf(
            Expression::class,
            Fragment::of(Str::of('{#foo*}'))->match(
                static fn($expression) => $expression,
                static fn() => null,
            ),
        );
        $this->assertInstanceOf(
            Expression::class,
            Fragment::of(Str::of('{#foo:42}'))->match(
                static fn($expression) => $expression,
                static fn() => null,
            ),
        );
    }

    public function testStringCast()
    {
        $this->assertSame(
            '{#foo}',
            Fragment::of(Str::of('{#foo}'))->match(
                static fn($expression) => $expression->toString(),
                static fn() => null,
            ),
        );
        $this->assertSame(
            '{#foo*}',
            Fragment::of(Str::of('{#foo*}'))->match(
                static fn($expression) => $expression->toString(),
                static fn() => null,
            ),
        );
        $this->assertSame(
            '{#foo:42}',
            Fragment::of(Str::of('{#foo:42}'))->match(
                static fn($expression) => $expression->toString(),
                static fn() => null,
            ),
        );
    }

    public function testReturnNothingWhenNegativeLimit(): BlackBox\Proof
    {
        return $this
            ->forAll(Set::integers()->below(1))
            ->prove(function(int $int): void {
                $this->assertNull(Fragment::of(Str::of("{#list:$int}"))->match(
                    static fn($expression) => $expression,
                    static fn() => null,
                ));
            });
    }

    public function testExpand()
    {
        $values = Map::of()
            ('var', 'value')
            ('hello', 'Hello World!')
            ('path', '/foo/bar');
        $lists = Map::of()
            ('list', ['red', 'green', 'blue']);
        $keys = Map::of()
            ('keys', [
                [Name::of('semi'), ';'],
                [Name::of('dot'), '.'],
                [Name::of('comma'), ','],
            ]);

        $this->assertSame(
            '#/foo/b',
            Fragment::of(Str::of('{#path:6}'))->match(
                static fn($expression) => $expression->expand($values, $lists, $keys),
                static fn() => null,
            ),
        );
        $this->assertSame(
            '#red,green,blue',
            Fragment::of(Str::of('{#list}'))->match(
                static fn($expression) => $expression->expand($values, $lists, $keys),
                static fn() => null,
            ),
        );
        $this->assertSame(
            '#red,green,blue',
            Fragment::of(Str::of('{#list*}'))->match(
                static fn($expression) => $expression->expand($values, $lists, $keys),
                static fn() => null,
            ),
        );
        $this->assertSame(
            '#semi,;,dot,.,comma,,',
            Fragment::of(Str::of('{#keys}'))->match(
                static fn($expression) => $expression->expand($values, $lists, $keys),
                static fn() => null,
            ),
        );
        $this->assertSame(
            '#semi=;,dot=.,comma=,',
            Fragment::of(Str::of('{#keys*}'))->match(
                static fn($expression) => $expression->expand($values, $lists, $keys),
                static fn() => null,
            ),
        );
    }

    public function testOf()
    {
        $this->assertInstanceOf(
            Fragment::class,
            $expression = Fragment::of(Str::of('{#foo}'))->match(
                static fn($expression) => $expression,
                static fn() => null,
            ),
        );
        $this->assertSame('{#foo}', $expression->toString());
        $this->assertInstanceOf(
            Fragment::class,
            $expression = Fragment::of(Str::of('{#foo*}'))->match(
                static fn($expression) => $expression,
                static fn() => null,
            ),
        );
        $this->assertSame('{#foo*}', $expression->toString());
        $this->assertInstanceOf(
            Fragment::class,
            $expression = Fragment::of(Str::of('{#foo:42}'))->match(
                static fn($expression) => $expression,
                static fn() => null,
            ),
        );
        $this->assertSame('{#foo:42}', $expression->toString());
    }

    public function testReturnNothingWhenInvalidPattern()
    {
        $this->assertNull(Fragment::of(Str::of('{foo}'))->match(
            static fn($expression) => $expression,
            static fn() => null,
        ));
    }

    public function testThrowExplodeRegex()
    {
        $this->expectException(\LogicException::class);

        $_ = Fragment::of(Str::of('{#foo*}'))->match(
            static fn($expression) => $expression->regex()->unwrap(),
            static fn() => null,
        );
    }

    public function testRegex()
    {
        $this->assertSame(
            '\#(?<foo>[a-zA-Z0-9\%:/\?#\[\]@!$&\'\(\)\*\+,;=\-\.\_\~]*)',
            Fragment::of(Str::of('{#foo}'))->match(
                static fn($expression) => $expression->regex()->unwrap(),
                static fn() => null,
            ),
        );
        $this->assertSame(
            '\#(?<foo>[a-zA-Z0-9\%:/\?#\[\]@!$&\'\(\)\*\+,;=\-\.\_\~]{2})',
            Fragment::of(Str::of('{#foo:2}'))->match(
                static fn($expression) => $expression->regex()->unwrap(),
                static fn() => null,
            ),
        );
    }
}
