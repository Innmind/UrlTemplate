<?php
declare(strict_types = 1);

namespace Tests\Innmind\UrlTemplate;

use Innmind\UrlTemplate\UrlEncode;
use PHPUnit\Framework\TestCase;
use Eris\{
    Generator,
    TestTrait,
};

class UrlEncodeTest extends TestCase
{
    use TestTrait;

    public function testStandardEncode()
    {
        $this
            ->forAll(Generator\string())
            ->then(function(string $string): void {
                $encode = new UrlEncode;

                $this->assertSame(rawurlencode($string), $encode($string));
            });
    }

    public function testSafeCharactersAreNotEncoded()
    {
        $this
            ->forAll(Generator\elements(
                ':',
                '/',
                '?',
                '#',
                '[',
                ']',
                '@',
                '!',
                '$',
                '&',
                '\'',
                '(',
                ')',
                '*',
                '+',
                ',',
                ';',
                '='
            ))
            ->then(function(string $char): void {
                $encode = new UrlEncode($char);

                $this->assertSame($char, $encode($char));
            });
    }

    public function testSafeCharactersAreNotEncodedEvenWhenInMiddleOfString()
    {
        $encode = new UrlEncode(':)');

        $this->assertSame(
            ':%2F%3F%23%5B%5D%40%21%24%26%27%28)%2A%2B%2C%3B%3D',
            $encode(':/?#[]@!$&\'()*+,;=')
        );
    }
}
