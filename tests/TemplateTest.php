<?php
declare(strict_types = 1);

namespace Tests\Innmind\UrlTemplate;

use Innmind\UrlTemplate\Template;
use Innmind\Url\Url;
use Innmind\Immutable\Map;
use Innmind\BlackBox\PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;

class TemplateTest extends TestCase
{
    public function testInterface()
    {
        $template = Template::of('http://example.com/{/folders}');

        $this->assertSame('http://example.com/{/folders}', $template->toString());
    }

    public function testOf()
    {
        $template = Template::of('http://example.com/{/folders}');

        $this->assertInstanceOf(Template::class, $template);
        $this->assertSame('http://example.com/{/folders}', $template->toString());
    }

    #[DataProvider('cases')]
    public function testExpand($pattern, $expected)
    {
        $template = Template::of($pattern);

        $url = $template
            ->expansion()
            ->with('var', 'value')
            ->with('hello', 'Hello World!')
            ->with('path', '/foo/bar')
            ->with('list', 'red', 'green', 'blue')
            ->withKeys('keys', ['semi', ';'], ['dot', '.'], ['comma', ','])
            ->with('username', 'fred')
            ->with('term', 'dog')
            ->with('q', 'chien')
            ->with('lang', 'fr')
            ->with('x', '1024')
            ->with('y', '768')
            ->expand();

        $this->assertInstanceOf(Url::class, $url);
        $this->assertSame($expected, $url->toString());
    }

    public function testReturnEmptyMapWhenUrlDoesntMatchTemplate()
    {
        $this->assertSame(
            0,
            Template::of('/{foo}')
                ->extract(Url::of('/hello%20world%21/foo'))
                ->unwrap()
                ->size(),
        );
    }

    public function testLevel1Extraction()
    {
        $variables = Template::of('/{foo}/{bar}')
            ->extract(Url::of('/hello%20world%21/foo'))
            ->unwrap();

        $this->assertInstanceOf(Map::class, $variables);
        $this->assertSame(2, $variables->size());
        $this->assertSame('hello world!', $variables->get('foo')->match(
            static fn($value) => $value,
            static fn() => null,
        ));
        $this->assertSame('foo', $variables->get('bar')->match(
            static fn($value) => $value,
            static fn() => null,
        ));
    }

    public function testLevel2Extraction()
    {
        $variables = Template::of('{+path}/here')
            ->extract(Url::of('/foo/bar/here'))
            ->unwrap();

        $this->assertInstanceOf(Map::class, $variables);
        $this->assertSame(1, $variables->size());
        $this->assertSame('/foo/bar', $variables->get('path')->match(
            static fn($value) => $value,
            static fn() => null,
        ));

        $variables = Template::of('X{#hello}')
            ->extract(Url::of('X#Hello%20World!'))
            ->unwrap();

        $this->assertInstanceOf(Map::class, $variables);
        $this->assertSame(1, $variables->size());
        $this->assertSame('Hello World!', $variables->get('hello')->match(
            static fn($value) => $value,
            static fn() => null,
        ));
    }

    public function testLevel3Extraction()
    {
        $variables = Template::of('/map?{x,y}')
            ->extract(Url::of('/map?1024,768'))
            ->unwrap();

        $this->assertInstanceOf(Map::class, $variables);
        $this->assertSame(2, $variables->size());
        $this->assertSame('1024', $variables->get('x')->match(
            static fn($value) => $value,
            static fn() => null,
        ));
        $this->assertSame('768', $variables->get('y')->match(
            static fn($value) => $value,
            static fn() => null,
        ));

        $variables = Template::of('/{x,hello,y}')
            ->extract(Url::of('/1024,Hello%20World%21,768'))
            ->unwrap();

        $this->assertInstanceOf(Map::class, $variables);
        $this->assertSame(3, $variables->size());
        $this->assertSame('1024', $variables->get('x')->match(
            static fn($value) => $value,
            static fn() => null,
        ));
        $this->assertSame('Hello World!', $variables->get('hello')->match(
            static fn($value) => $value,
            static fn() => null,
        ));
        $this->assertSame('768', $variables->get('y')->match(
            static fn($value) => $value,
            static fn() => null,
        ));

        $variables = Template::of('/{+x,hello,y}')
            ->extract(Url::of('/1024,Hello%20World!,768'))
            ->unwrap();

        $this->assertInstanceOf(Map::class, $variables);
        $this->assertSame(3, $variables->size());
        $this->assertSame('1024', $variables->get('x')->match(
            static fn($value) => $value,
            static fn() => null,
        ));
        $this->assertSame('Hello World!', $variables->get('hello')->match(
            static fn($value) => $value,
            static fn() => null,
        ));
        $this->assertSame('768', $variables->get('y')->match(
            static fn($value) => $value,
            static fn() => null,
        ));

        $variables = Template::of('{+path,x}/here')
            ->extract(Url::of('/foo/bar,1024/here'))
            ->unwrap();

        $this->assertInstanceOf(Map::class, $variables);
        $this->assertSame(2, $variables->size());
        $this->assertSame('1024', $variables->get('x')->match(
            static fn($value) => $value,
            static fn() => null,
        ));
        $this->assertSame('/foo/bar', $variables->get('path')->match(
            static fn($value) => $value,
            static fn() => null,
        ));

        $variables = Template::of('{#x,hello,y}')
            ->extract(Url::of('#1024,Hello%20World!,768'))
            ->unwrap();

        $this->assertInstanceOf(Map::class, $variables);
        $this->assertSame(3, $variables->size());
        $this->assertSame('1024', $variables->get('x')->match(
            static fn($value) => $value,
            static fn() => null,
        ));
        $this->assertSame('Hello World!', $variables->get('hello')->match(
            static fn($value) => $value,
            static fn() => null,
        ));
        $this->assertSame('768', $variables->get('y')->match(
            static fn($value) => $value,
            static fn() => null,
        ));

        $variables = Template::of('{#path,x}/here')
            ->extract(Url::of('#/foo/bar,1024/here'))
            ->unwrap();

        $this->assertInstanceOf(Map::class, $variables);
        $this->assertSame(2, $variables->size());
        $this->assertSame('1024', $variables->get('x')->match(
            static fn($value) => $value,
            static fn() => null,
        ));
        $this->assertSame('/foo/bar', $variables->get('path')->match(
            static fn($value) => $value,
            static fn() => null,
        ));

        $variables = Template::of('{.x,y}')
            ->extract(Url::of('.1024.768'))
            ->unwrap();

        $this->assertInstanceOf(Map::class, $variables);
        $this->assertSame(2, $variables->size());
        $this->assertSame('1024', $variables->get('x')->match(
            static fn($value) => $value,
            static fn() => null,
        ));
        $this->assertSame('768', $variables->get('y')->match(
            static fn($value) => $value,
            static fn() => null,
        ));

        $variables = Template::of('{/var,x}/here')
            ->extract(Url::of('/value/1024/here'))
            ->unwrap();

        $this->assertInstanceOf(Map::class, $variables);
        $this->assertSame(2, $variables->size());
        $this->assertSame('value', $variables->get('var')->match(
            static fn($value) => $value,
            static fn() => null,
        ));
        $this->assertSame('1024', $variables->get('x')->match(
            static fn($value) => $value,
            static fn() => null,
        ));

        $variables = Template::of('{;x,y}')
            ->extract(Url::of(';x=1024;y=768'))
            ->unwrap();

        $this->assertInstanceOf(Map::class, $variables);
        $this->assertSame(2, $variables->size());
        $this->assertSame('1024', $variables->get('x')->match(
            static fn($value) => $value,
            static fn() => null,
        ));
        $this->assertSame('768', $variables->get('y')->match(
            static fn($value) => $value,
            static fn() => null,
        ));

        $variables = Template::of('{;x,y,empty}')
            ->extract(Url::of(';x=1024;y=768;empty'))
            ->unwrap();

        $this->assertInstanceOf(Map::class, $variables);
        $this->assertSame(3, $variables->size());
        $this->assertSame('1024', $variables->get('x')->match(
            static fn($value) => $value,
            static fn() => null,
        ));
        $this->assertSame('768', $variables->get('y')->match(
            static fn($value) => $value,
            static fn() => null,
        ));
        $this->assertSame('', $variables->get('empty')->match(
            static fn($value) => $value,
            static fn() => null,
        ));

        $variables = Template::of('{?x,y}')
            ->extract(Url::of('?x=1024&y=768'))
            ->unwrap();

        $this->assertInstanceOf(Map::class, $variables);
        $this->assertSame(2, $variables->size());
        $this->assertSame('1024', $variables->get('x')->match(
            static fn($value) => $value,
            static fn() => null,
        ));
        $this->assertSame('768', $variables->get('y')->match(
            static fn($value) => $value,
            static fn() => null,
        ));

        $variables = Template::of('{?x,y,empty}')
            ->extract(Url::of('?x=1024&y=768&empty='))
            ->unwrap();

        $this->assertInstanceOf(Map::class, $variables);
        $this->assertSame(3, $variables->size());
        $this->assertSame('1024', $variables->get('x')->match(
            static fn($value) => $value,
            static fn() => null,
        ));
        $this->assertSame('768', $variables->get('y')->match(
            static fn($value) => $value,
            static fn() => null,
        ));
        $this->assertSame('', $variables->get('empty')->match(
            static fn($value) => $value,
            static fn() => null,
        ));

        $variables = Template::of('?fixed=yes{&x}')
            ->extract(Url::of('?fixed=yes&x=1024'))
            ->unwrap();

        $this->assertInstanceOf(Map::class, $variables);
        $this->assertSame(1, $variables->size());
        $this->assertSame('1024', $variables->get('x')->match(
            static fn($value) => $value,
            static fn() => null,
        ));

        $variables = Template::of('{&x,y,empty}')
            ->extract(Url::of('&x=1024&y=768&empty='))
            ->unwrap();

        $this->assertInstanceOf(Map::class, $variables);
        $this->assertSame(3, $variables->size());
        $this->assertSame('1024', $variables->get('x')->match(
            static fn($value) => $value,
            static fn() => null,
        ));
        $this->assertSame('768', $variables->get('y')->match(
            static fn($value) => $value,
            static fn() => null,
        ));
        $this->assertSame('', $variables->get('empty')->match(
            static fn($value) => $value,
            static fn() => null,
        ));
    }

    public function testLevel4Extraction()
    {
        $variables = Template::of('{var:3}')
            ->extract(Url::of('val'))
            ->unwrap();

        $this->assertInstanceOf(Map::class, $variables);
        $this->assertSame(1, $variables->size());
        $this->assertSame('val', $variables->get('var')->match(
            static fn($value) => $value,
            static fn() => null,
        ));

        $variables = Template::of('{+path:6}/here')
            ->extract(Url::of('/foo/b/here'))
            ->unwrap();

        $this->assertInstanceOf(Map::class, $variables);
        $this->assertSame(1, $variables->size());
        $this->assertSame('/foo/b', $variables->get('path')->match(
            static fn($value) => $value,
            static fn() => null,
        ));

        $variables = Template::of('{#path:6}/here')
            ->extract(Url::of('#/foo/b/here'))
            ->unwrap();

        $this->assertInstanceOf(Map::class, $variables);
        $this->assertSame(1, $variables->size());
        $this->assertSame('/foo/b', $variables->get('path')->match(
            static fn($value) => $value,
            static fn() => null,
        ));

        $variables = Template::of('{.var:3}')
            ->extract(Url::of('.val'))
            ->unwrap();

        $this->assertInstanceOf(Map::class, $variables);
        $this->assertSame(1, $variables->size());
        $this->assertSame('val', $variables->get('var')->match(
            static fn($value) => $value,
            static fn() => null,
        ));

        $variables = Template::of('{/var:1}')
            ->extract(Url::of('/v'))
            ->unwrap();

        $this->assertInstanceOf(Map::class, $variables);
        $this->assertSame(1, $variables->size());
        $this->assertSame('v', $variables->get('var')->match(
            static fn($value) => $value,
            static fn() => null,
        ));

        $variables = Template::of('{;var:5}')
            ->extract(Url::of(';var=hello'))
            ->unwrap();

        $this->assertInstanceOf(Map::class, $variables);
        $this->assertSame(1, $variables->size());
        $this->assertSame('hello', $variables->get('var')->match(
            static fn($value) => $value,
            static fn() => null,
        ));

        $variables = Template::of('{?var:3}')
            ->extract(Url::of('?var=hel'))
            ->unwrap();

        $this->assertInstanceOf(Map::class, $variables);
        $this->assertSame(1, $variables->size());
        $this->assertSame('hel', $variables->get('var')->match(
            static fn($value) => $value,
            static fn() => null,
        ));

        $variables = Template::of('{&var:3}')
            ->extract(Url::of('&var=hel'))
            ->unwrap();

        $this->assertInstanceOf(Map::class, $variables);
        $this->assertSame(1, $variables->size());
        $this->assertSame('hel', $variables->get('var')->match(
            static fn($value) => $value,
            static fn() => null,
        ));
    }

    public function testExtraction()
    {
        $variables = Template::of('http://example.com/search{?q,lang:2}')
            ->extract(Url::of('http://example.com/search?q=chien&lang=fr'))
            ->unwrap();

        $this->assertInstanceOf(Map::class, $variables);
        $this->assertSame(2, $variables->size());
        $this->assertSame('chien', $variables->get('q')->match(
            static fn($value) => $value,
            static fn() => null,
        ));
        $this->assertSame('fr', $variables->get('lang')->match(
            static fn($value) => $value,
            static fn() => null,
        ));
    }

    public function testThrowWhenExtractionNotSupportedForTemplate()
    {
        $this->expectException(\LogicException::class);

        $_ = Template::of('{foo*}')
            ->extract(Url::of('foo,bar,baz'))
            ->unwrap();
    }

    public function testMatches()
    {
        $template = Template::of('{/foo}');

        $this->assertTrue($template->matches(Url::of('/bar'))->unwrap());
        $this->assertFalse($template->matches(Url::of('/bar/foo'))->unwrap());
    }

    public function testNoNeedToEscapeSpecialRegexCharactersInTheUrl()
    {
        $template = Template::of('/*');

        $this->assertTrue($template->matches(Url::of('/*'))->unwrap());
        $this->assertFalse($template->matches(Url::of('/f'))->unwrap());
    }

    public static function cases(): array
    {
        return [
            ['http://example.com{/list*}{?keys*}', 'http://example.com/red/green/blue?semi=%3B&dot=.&comma=%2C'],
            ['http://example.com/~{username}/', 'http://example.com/~fred/'],
            ['http://example.com/dictionary/{term:1}/{term}', 'http://example.com/dictionary/d/dog'],
            ['http://example.com/search{?q,lang}', 'http://example.com/search?q=chien&lang=fr'],
            ['{var}', 'value'],
            ['{hello}', 'Hello%20World%21'],
            ['{+var}', 'value'],
            ['{+hello}', 'Hello%20World!'],
            ['{+path}/here', '/foo/bar/here'],
            ['here?ref={+path}', 'here?ref=/foo/bar'],
            ['X{#var}', 'X#value'],
            ['X{#hello}', 'X#Hello%20World!'],
            ['map?{x,y}', 'map?1024,768'],
            ['{x,hello,y}', '1024,Hello%20World%21,768'],
            ['{+x,hello,y}', '1024,Hello%20World!,768'],
            ['{+path,x}/here', '/foo/bar,1024/here'],
            ['{#x,hello,y}', '#1024,Hello%20World!,768'],
            ['{#path,x}/here', '#/foo/bar,1024/here'],
            ['X{.var}', 'X.value'],
            ['X{.x,y}', 'X.1024.768'],
            ['{/var}', '/value'],
            ['{/var,x}/here', '/value/1024/here'],
            ['{;x,y}', ';x=1024;y=768'],
            ['{;x,y,empty}', ';x=1024;y=768;empty'],
            ['{?x,y}', '?x=1024&y=768'],
            ['{?x,y,empty}', '?x=1024&y=768&empty='],
            ['?fixed=yes{&x}', '?fixed=yes&x=1024'],
            ['{&x,y,empty}', '&x=1024&y=768&empty='],
            ['{var:3}', 'val'],
            ['{var:30}', 'value'],
            ['{list}', 'red,green,blue'],
            ['{list*}', 'red,green,blue'],
            ['{keys}', 'semi,%3B,dot,.,comma,%2C'],
            ['{keys*}', 'semi=%3B,dot=.,comma=%2C'],
            ['{+path:6}/here', '/foo/b/here'],
            ['{+list}', 'red,green,blue'],
            ['{+list*}', 'red,green,blue'],
            ['{+keys}', 'semi,;,dot,.,comma,,'],
            ['{+keys*}', 'semi=;,dot=.,comma=,'],
            ['{#path:6}/here', '#/foo/b/here'],
            ['{#list}', '#red,green,blue'],
            ['{#list*}', '#red,green,blue'],
            ['{#keys}', '#semi,;,dot,.,comma,,'],
            ['{#keys*}', '#semi=;,dot=.,comma=,'],
            ['X{.var:3}', 'X.val'],
            ['X{.list}', 'X.red,green,blue'],
            ['X{.list*}', 'X.red.green.blue'],
            ['X{.keys}', 'X.semi,%3B,dot,.,comma,%2C'],
            ['X{.keys*}', 'X.semi=%3B.dot=..comma=%2C'],
            ['{/var:1,var}', '/v/value'],
            ['{/list}', '/red,green,blue'],
            ['{/list*}', '/red/green/blue'],
            ['{/list*,path:4}', '/red/green/blue/%2Ffoo'],
            ['{/keys}', '/semi,%3B,dot,.,comma,%2C'],
            ['{/keys*}', '/semi=%3B/dot=./comma=%2C'],
            ['{;hello:5}', ';hello=Hello'],
            ['{;list}', ';list=red,green,blue'],
            ['{;list*}', ';list=red;list=green;list=blue'],
            ['{;keys}', ';keys=semi,%3B,dot,.,comma,%2C'],
            ['{;keys*}', ';semi=%3B;dot=.;comma=%2C'],
            ['{?var:3}', '?var=val'],
            ['{?list}', '?list=red,green,blue'],
            ['{?list*}', '?list=red&list=green&list=blue'],
            ['{?keys}', '?keys=semi,%3B,dot,.,comma,%2C'],
            ['{?keys*}', '?semi=%3B&dot=.&comma=%2C'],
            ['{&var:3}', '&var=val'],
            ['{&list}', '&list=red,green,blue'],
            ['{&list*}', '&list=red&list=green&list=blue'],
            ['{&keys}', '&keys=semi,%3B,dot,.,comma,%2C'],
            ['{&keys*}', '&semi=%3B&dot=.&comma=%2C'],
        ];
    }
}
