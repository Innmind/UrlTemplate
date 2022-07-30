<?php
declare(strict_types = 1);

namespace Tests\Innmind\UrlTemplate\Expression\Level4;

use Innmind\UrlTemplate\{
    Expression\Level4\Path,
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

class PathTest extends TestCase
{
    use BlackBox;

    public function testInterface()
    {
        $this->assertInstanceOf(
            Expression::class,
            Path::of(Str::of('{/foo}')),
        );
        $this->assertInstanceOf(
            Expression::class,
            Path::of(Str::of('{/foo*}')),
        );
        $this->assertInstanceOf(
            Expression::class,
            Path::of(Str::of('{/foo:42}')),
        );
    }

    public function testStringCast()
    {
        $this->assertSame('{/foo}', Path::of(Str::of('{/foo}'))->toString());
        $this->assertSame('{/foo*}', Path::of(Str::of('{/foo*}'))->toString());
        $this->assertSame('{/foo:42}', Path::of(Str::of('{/foo:42}'))->toString());
    }

    public function testThrowWhenNegativeLimit()
    {
        $this
            ->forAll(Set\Integers::below(1))
            ->then(function(int $int): void {
                $this->expectException(DomainException::class);

                Path::of(Str::of("{/foo:$int}"));
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
            '/red,green,blue',
            Path::of(Str::of('{/list}'))->expand($variables),
        );
        $this->assertSame(
            '/red/green/blue',
            Path::of(Str::of('{/list*}'))->expand($variables),
        );
        $this->assertSame(
            '/semi,%3B,dot,.,comma,%2C',
            Path::of(Str::of('{/keys}'))->expand($variables),
        );
        $this->assertSame(
            '/semi=%3B/dot=./comma=%2C',
            Path::of(Str::of('{/keys*}'))->expand($variables),
        );
    }

    public function testOf()
    {
        $this->assertInstanceOf(
            Path::class,
            $expression = Path::of(Str::of('{/foo}')),
        );
        $this->assertSame('{/foo}', $expression->toString());
        $this->assertInstanceOf(
            Path::class,
            $expression = Path::of(Str::of('{/foo*}')),
        );
        $this->assertSame('{/foo*}', $expression->toString());
        $this->assertInstanceOf(
            Path::class,
            $expression = Path::of(Str::of('{/foo:42}')),
        );
        $this->assertSame('{/foo:42}', $expression->toString());
    }

    public function testThrowWhenInvalidPattern()
    {
        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('{foo}');

        Path::of(Str::of('{foo}'));
    }

    public function testThrowExplodeRegex()
    {
        $this->expectException(LogicException::class);

        Path::of(Str::of('{/foo*}'))->regex();
    }

    public function testRegex()
    {
        $this->assertSame(
            '\/(?<foo>[a-zA-Z0-9\%\-\.\_\~]*)',
            Path::of(Str::of('{/foo}'))->regex(),
        );
        $this->assertSame(
            '\/(?<foo>[a-zA-Z0-9\%\-\.\_\~]{2})',
            Path::of(Str::of('{/foo:2}'))->regex(),
        );
    }
}
