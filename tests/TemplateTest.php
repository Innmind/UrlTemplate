<?php
declare(strict_types = 1);

namespace Tests\Innmind\UrlTemplate;

use Innmind\UrlTemplate\Template;
use Innmind\Url\UrlInterface;
use Innmind\Immutable\{
    Map,
    SetInterface,
};
use PHPUnit\Framework\TestCase;

class TemplateTest extends TestCase
{
    public function testInterface()
    {
        $template = new Template('http://example.com/{/folders}');

        $this->assertSame('http://example.com/{/folders}', (string) $template);
        $this->assertInstanceOf(SetInterface::class, $template->expressions());
        $this->assertCount(1, $template->expressions());
        $this->assertSame('{/folders}', (string) $template->expressions()->current());
    }

    public function testOf()
    {
        $template = Template::of('http://example.com/{/folders}');

        $this->assertInstanceOf(Template::class, $template);
        $this->assertSame('http://example.com/{/folders}', (string) $template);
    }

    /**
     * @dataProvider cases
     */
    public function testExpand($pattern, $expected)
    {
        $variables = (new Map('string', 'variable'))
            ->put('var', 'value')
            ->put('hello', 'Hello World!')
            ->put('path', '/foo/bar')
            ->put('list', ['red', 'green', 'blue'])
            ->put('keys', [['semi', ';'], ['dot', '.'], ['comma', ',']])
            ->put('username', 'fred')
            ->put('term', 'dog')
            ->put('q', 'chien')
            ->put('lang', 'fr')
            ->put('x', '1024')
            ->put('y', '768');

        $template = Template::of($pattern);

        $url = $template->expand($variables);

        $this->assertInstanceOf(UrlInterface::class, $url);
        $this->assertSame($expected, (string) $url);
    }

    public function testThrowWhenInvalidVariablesKeyType()
    {
        $this->expectException(\TypeError::class);
        $this->expectExceptionMessage('Argument 1 must be of type MapInterface<string, variable>');

        Template::of('foo')->expand(new Map('int', 'variable'));
    }

    public function testThrowWhenInvalidVariablesValueType()
    {
        $this->expectException(\TypeError::class);
        $this->expectExceptionMessage('Argument 1 must be of type MapInterface<string, variable>');

        Template::of('foo')->expand(new Map('string', 'string'));
    }

    public function cases(): array
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
