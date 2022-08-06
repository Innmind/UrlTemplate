<?php
declare(strict_types = 1);

namespace Tests\Innmind\UrlTemplate;

use Innmind\UrlTemplate\UrlEncode;
use PHPUnit\Framework\TestCase;
use Innmind\BlackBox\{
    PHPUnit\BlackBox,
    Set,
};

class UrlEncodeTest extends TestCase
{
    use BlackBox;

    public function testStandardEncode()
    {
        $this
            ->forAll(Set\Strings::any())
            ->then(function(string $string): void {
                $encode = new UrlEncode;

                $this->assertSame(\rawurlencode($string), $encode($string));
            });
    }

    public function testSafeCharactersAreNotEncoded()
    {
        $this
            ->forAll(Set\Elements::of(
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
                '=',
            ))
            ->then(function(string $char): void {
                $encode = UrlEncode::allowReservedCharacters();

                $this->assertSame($char, $encode($char));
            });
    }

    public function testSafeCharactersAreNotEncodedEvenWhenInMiddleOfString()
    {
        $encode = UrlEncode::allowReservedCharacters();

        $this->assertSame(
            ':%20)',
            $encode(': )'),
        );
    }

    public function testDoesNothingOnEmptyString()
    {
        $this->assertSame('', UrlEncode::allowReservedCharacters()(''));
    }
}
