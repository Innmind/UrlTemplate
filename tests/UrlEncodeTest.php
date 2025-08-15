<?php
declare(strict_types = 1);

namespace Tests\Innmind\UrlTemplate;

use Innmind\UrlTemplate\UrlEncode;
use Innmind\BlackBox\{
    PHPUnit\BlackBox,
    PHPUnit\Framework\TestCase,
    Set,
};

class UrlEncodeTest extends TestCase
{
    use BlackBox;

    public function testStandardEncode(): BlackBox\Proof
    {
        return $this
            ->forAll(Set::strings())
            ->prove(function(string $string): void {
                $encode = new UrlEncode;

                $this->assertSame(\rawurlencode($string), $encode($string));
            });
    }

    public function testSafeCharactersAreNotEncoded(): BlackBox\Proof
    {
        return $this
            ->forAll(Set::of(
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
            ->prove(function(string $char): void {
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
