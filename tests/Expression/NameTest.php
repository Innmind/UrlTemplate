<?php
declare(strict_types = 1);

namespace Tests\Innmind\UrlTemplate\Expression;

use Innmind\UrlTemplate\{
    Expression\Name,
    Exception\DomainException,
};
use PHPUnit\Framework\TestCase;
use Innmind\BlackBox\{
    PHPUnit\BlackBox,
    Set,
};

class NameTest extends TestCase
{
    use BlackBox;

    public function testInterface()
    {
        $this
            ->forAll(
                Set\Strings::madeOf(
                    Set\Chars::lowercaseLetter(),
                    Set\Chars::uppercaseLetter(),
                    Set\Chars::number(),
                    Set\Elements::of('_'),
                )->atLeast(1),
            )
            ->then(function(string $string): void {
                $this->assertSame($string, Name::of($string)->toString());
            });
    }

    public function testThrowWhenInvalidName()
    {
        $this
            ->forAll(
                Set\Strings::any()->filter(static function(string $string): bool {
                    return (bool) !\preg_match('~^[a-zA-Z0-9_]+$~', $string);
                }),
            )
            ->then(function(string $string): void {
                $this->expectException(DomainException::class);
                $this->expectExceptionMessage($string);

                Name::of($string);
            });
    }
}
