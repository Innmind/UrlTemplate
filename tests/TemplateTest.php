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

    public function testExpand()
    {
        $template = Template::of('http://example.com{/folders*}{?query*}');

        $url = $template->expand(
            (new Map('string', 'variable'))
                ->put('folders', ['foo', 'bar', 'baz'])
                ->put('query', [['foo', 'bar'], ['bar', 'baz']])
        );

        $this->assertInstanceOf(UrlInterface::class, $url);
        $this->assertSame(
            'http://example.com/foo/bar/baz?foo=bar&bar=baz',
            (string) $url
        );
    }
}
