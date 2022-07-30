<?php
declare(strict_types = 1);

namespace Tests\Innmind\UrlTemplate\Expression\Level3;

use Innmind\UrlTemplate\{
    Expression\Level3\Parameters,
    Expression\Name,
    Expression,
    Exception\DomainException,
};
use Innmind\Immutable\{
    Map,
    Str,
    Sequence,
};
use PHPUnit\Framework\TestCase;

class ParametersTest extends TestCase
{
    public function testInterface()
    {
        $this->assertInstanceOf(
            Expression::class,
            new Parameters(Sequence::of(new Name('foo'), new Name('bar'))),
        );
    }

    public function testStringCast()
    {
        $this->assertSame(
            '{;foo,bar}',
            (new Parameters(Sequence::of(new Name('foo'), new Name('bar'))))->toString(),
        );
    }

    public function testExpand()
    {
        $variables = Map::of()
            ('var', 'value')
            ('hello', 'Hello World!')
            ('empty', '')
            ('path', '/foo/bar')
            ('x', '1024')
            ('y', '768');

        $this->assertSame(
            ';x=1024;y=768',
            (new Parameters(Sequence::of(new Name('x'), new Name('y'))))->expand($variables),
        );
        $this->assertSame(
            ';x=1024;y=768;empty',
            (new Parameters(Sequence::of(new Name('x'), new Name('y'), new Name('empty'))))->expand($variables),
        );
    }

    public function testOf()
    {
        $this->assertInstanceOf(
            Parameters::class,
            $expression = Parameters::of(Str::of('{;foo,bar}')),
        );
        $this->assertSame('{;foo,bar}', $expression->toString());
    }

    public function testThrowWhenInvalidPattern()
    {
        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('{;foo}');

        Parameters::of(Str::of('{;foo}'));
    }

    public function testRegex()
    {
        $this->assertSame(
            '\;foo=?(?<foo>[a-zA-Z0-9\%\-\.\_\~]*)\;bar=?(?<bar>[a-zA-Z0-9\%\-\.\_\~]*)',
            Parameters::of(Str::of('{;foo,bar}'))->regex(),
        );
    }
}
