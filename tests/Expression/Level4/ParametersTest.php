<?php
declare(strict_types = 1);

namespace Tests\Innmind\UrlTemplate\Expression\Level4;

use Innmind\UrlTemplate\{
    Expression\Level4\Parameters,
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
use Innmind\BlackBox\{
    PHPUnit\BlackBox,
    Set,
};

class ParametersTest extends TestCase
{
    use BlackBox;

    public function testInterface()
    {
        $this->assertInstanceOf(
            Expression::class,
            new Parameters(new Name('foo')),
        );
        $this->assertInstanceOf(
            Expression::class,
            Parameters::explode(new Name('foo')),
        );
        $this->assertInstanceOf(
            Expression::class,
            Parameters::limit(new Name('foo'), 42),
        );
    }

    public function testStringCast()
    {
        $this->assertSame('{;foo}', (new Parameters(new Name('foo')))->toString());
        $this->assertSame('{;foo*}', Parameters::explode(new Name('foo'))->toString());
        $this->assertSame('{;foo:42}', Parameters::limit(new Name('foo'), 42)->toString());
    }

    public function testThrowWhenNegativeLimit()
    {
        $this
            ->forAll(Set\Integers::below(1))
            ->then(function(int $int): void {
                $this->expectException(DomainException::class);

                Parameters::limit(new Name('foo'), $int);
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
            ';hello=Hello',
            Parameters::limit(new Name('hello'), 5)->expand($variables),
        );
        $this->assertSame(
            ';list=red,green,blue',
            (new Parameters(new Name('list')))->expand($variables),
        );
        $this->assertSame(
            ';list=red;list=green;list=blue',
            Parameters::explode(new Name('list'))->expand($variables),
        );
        $this->assertSame(
            ';keys=semi,%3B,dot,.,comma,%2C',
            (new Parameters(new Name('keys')))->expand($variables),
        );
        $this->assertSame(
            ';semi=%3B;dot=.;comma=%2C',
            Parameters::explode(new Name('keys'))->expand($variables),
        );
    }

    public function testOf()
    {
        $this->assertInstanceOf(
            Parameters::class,
            $expression = Parameters::of(Str::of('{;foo}')),
        );
        $this->assertSame('{;foo}', $expression->toString());
        $this->assertInstanceOf(
            Parameters::class,
            $expression = Parameters::of(Str::of('{;foo*}')),
        );
        $this->assertSame('{;foo*}', $expression->toString());
        $this->assertInstanceOf(
            Parameters::class,
            $expression = Parameters::of(Str::of('{;foo:42}')),
        );
        $this->assertSame('{;foo:42}', $expression->toString());
    }

    public function testThrowWhenInvalidPattern()
    {
        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('{foo}');

        Parameters::of(Str::of('{foo}'));
    }

    public function testThrowExplodeRegex()
    {
        $this->expectException(LogicException::class);

        Parameters::of(Str::of('{;foo*}'))->regex();
    }

    public function testRegex()
    {
        $this->assertSame(
            '\;foo=(?<foo>[a-zA-Z0-9\%\-\.\_\~]*)',
            Parameters::of(Str::of('{;foo}'))->regex(),
        );
        $this->assertSame(
            '\;foo=(?<foo>[a-zA-Z0-9\%\-\.\_\~]{2})',
            Parameters::of(Str::of('{;foo:2}'))->regex(),
        );
    }
}
