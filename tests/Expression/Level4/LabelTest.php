<?php
declare(strict_types = 1);

namespace Tests\Innmind\UrlTemplate\Expression\Level4;

use Innmind\UrlTemplate\{
    Expression\Level4\Label,
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
        $variables = Map::of('string', 'variable')
            ('var', 'value')
            ('hello', 'Hello World!')
            ('path', '/foo/bar')
            ('list', ['red', 'green', 'blue'])
            ('keys', [['semi', ';'], ['dot', '.'], ['comma', ',']]);

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
            '.semi,%3B,dot,.,comma,%2C',
            (new Label(new Name('keys')))->expand($variables)
        );
        $this->assertSame(
            '.semi=%3B.dot=..comma=%2C',
            Label::explode(new Name('keys'))->expand($variables)
        );
    }

    public function testOf()
    {
        $this->assertInstanceOf(
            Label::class,
            $expression = Label::of(Str::of('{.foo}'))
        );
        $this->assertSame('{.foo}', (string) $expression);
        $this->assertInstanceOf(
            Label::class,
            $expression = Label::of(Str::of('{.foo*}'))
        );
        $this->assertSame('{.foo*}', (string) $expression);
        $this->assertInstanceOf(
            Label::class,
            $expression = Label::of(Str::of('{.foo:42}'))
        );
        $this->assertSame('{.foo:42}', (string) $expression);
    }

    public function testThrowWhenInvalidPattern()
    {
        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('{foo}');

        Label::of(Str::of('{foo}'));
    }

    public function testThrowExplodeRegex()
    {
        $this->expectException(LogicException::class);

        Label::of(Str::of('{.foo*}'))->regex();
    }

    public function testRegex()
    {
        $this->assertSame(
            '\.(?<foo>[a-zA-Z0-9\%\-\.\_\~]*)',
            Label::of(Str::of('{.foo}'))->regex()
        );
        $this->assertSame(
            '\.(?<foo>[a-zA-Z0-9\%\-\.\_\~]{2})',
            Label::of(Str::of('{.foo:2}'))->regex()
        );
    }
}
