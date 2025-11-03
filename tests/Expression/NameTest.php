<?php
declare(strict_types = 1);

namespace Tests\Innmind\UrlTemplate\Expression;

use Innmind\UrlTemplate\Expression\Name;
use Innmind\BlackBox\{
    PHPUnit\BlackBox,
    PHPUnit\Framework\TestCase,
    Set,
};

class NameTest extends TestCase
{
    use BlackBox;

    public function testInterface(): BlackBox\Proof
    {
        return $this
            ->forAll(
                Set::strings()
                    ->madeOf(
                        Set::strings()->chars()->lowercaseLetter(),
                        Set::strings()->chars()->uppercaseLetter(),
                        Set::strings()->chars()->number(),
                        Set::of('_'),
                    )
                    ->atLeast(1),
            )
            ->prove(function(string $string): void {
                $this->assertSame($string, Name::of($string)->toString());
            });
    }

    public function testThrowWhenInvalidName(): BlackBox\Proof
    {
        return $this
            ->forAll(
                Set::strings()->filter(static function(string $string): bool {
                    return (bool) !\preg_match('~^[a-zA-Z0-9_]+$~', $string);
                }),
            )
            ->prove(function(string $string): void {
                $this->expectException(\DomainException::class);
                $this->expectExceptionMessage($string);

                Name::of($string);
            });
    }
}
