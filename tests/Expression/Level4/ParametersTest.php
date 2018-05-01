<?php
declare(strict_types = 1);

namespace Tests\Innmind\UrlTemplate\Expression\Level4;

use Innmind\UrlTemplate\{
    Expression\Level4\Parameters,
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

class ParametersTest extends TestCase
{
    use TestTrait;

    public function testInterface()
    {
        $this->assertInstanceOf(
            Expression::class,
            new Parameters(new Name('foo'))
        );
        $this->assertInstanceOf(
            Expression::class,
            Parameters::explode(new Name('foo'))
        );
        $this->assertInstanceOf(
            Expression::class,
            Parameters::limit(new Name('foo'), 42)
        );
    }

    public function testStringCast()
    {
        $this->assertSame('{;foo}', (string) new Parameters(new Name('foo')));
        $this->assertSame('{;foo*}', (string) Parameters::explode(new Name('foo')));
        $this->assertSame('{;foo:42}', (string) Parameters::limit(new Name('foo'), 42));
    }

    public function testThrowWhenNegativeLimit()
    {
        $this
            ->forAll(Generator\neg())
            ->then(function(int $int): void {
                $this->expectException(DomainException::class);

                Parameters::limit(new Name('foo'), $int);
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
            ';hello=Hello',
            Parameters::limit(new Name('hello'), 5)->expand($variables)
        );
        $this->assertSame(
            ';list=red,green,blue',
            (new Parameters(new Name('list')))->expand($variables)
        );
        $this->assertSame(
            ';list=red;list=green;list=blue',
            Parameters::explode(new Name('list'))->expand($variables)
        );
        $this->assertSame(
            ';keys=semi,%3B,dot,.,comma,%2C',
            (new Parameters(new Name('keys')))->expand($variables)
        );
        $this->assertSame(
            ';semi=%3B;dot=.;comma=%2C',
            Parameters::explode(new Name('keys'))->expand($variables)
        );
    }
}