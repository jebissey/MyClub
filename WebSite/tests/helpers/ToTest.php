<?php

declare(strict_types=1);

namespace Tests\helpers;

use PHPUnit\Framework\TestCase;
use RuntimeException;
use app\helpers\To;

final class ToTest extends TestCase
{
    // --- To::int() ---

    public function testIntReturnsIntUnchanged(): void
    {
        $this->assertSame(42, To::int(42));
    }

    public function testIntCastsNumericString(): void
    {
        $this->assertSame(42, To::int('42'));
    }

    public function testIntCastsFloat(): void
    {
        $this->assertSame(3, To::int(3.9));
    }

    public function testIntReturnsDefaultForNonNumeric(): void
    {
        $this->assertSame(0, To::int('abc'));
    }

    public function testIntReturnsCustomDefaultForNull(): void
    {
        $this->assertSame(-1, To::int(null, -1));
    }

    // --- To::str() ---

    public function testStrReturnsStringUnchanged(): void
    {
        $this->assertSame('hello', To::str('hello'));
    }

    public function testStrCastsInt(): void
    {
        $this->assertSame('42', To::str(42));
    }

    public function testStrCastsBool(): void
    {
        $this->assertSame('1', To::str(true));
        $this->assertSame('', To::str(false));
    }

    public function testStrReturnsDefaultForArray(): void
    {
        $this->assertSame('n/a', To::str(['x'], 'n/a'));
    }

    public function testStrReturnsDefaultForNull(): void
    {
        $this->assertSame('', To::str(null));
    }

    // --- To::float() ---

    public function testFloatCastsInt(): void
    {
        $this->assertSame(3.0, To::float(3));
    }

    public function testFloatCastsNumericString(): void
    {
        $this->assertSame(3.14, To::float('3.14'));
    }

    public function testFloatThrowsOnNonNumericString(): void
    {
        $this->expectException(RuntimeException::class);
        To::float('abc');
    }

    public function testFloatThrowsOnArray(): void
    {
        $this->expectException(RuntimeException::class);
        To::float(['x']);
    }
}