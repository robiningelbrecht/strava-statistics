<?php

namespace App\Tests\Infrastructure\ValueObject\Number;

use App\Infrastructure\ValueObject\Number\PositiveInteger;
use PHPUnit\Framework\TestCase;

class PositiveIntegerTest extends TestCase
{
    public function testFromInt(): void
    {
        $this->assertEquals(0, PositiveInteger::fromInt(0)->getValue());
        $this->assertEquals(33, PositiveInteger::fromInt(33)->getValue());
    }

    public function testFromOptionalInt(): void
    {
        $this->assertEquals(0, PositiveInteger::fromOptionalInt(0)->getValue());
        $this->assertEquals(33, PositiveInteger::fromOptionalInt(33)->getValue());
        $this->assertNull(PositiveInteger::fromOptionalInt(null));
    }

    public function testItShouldThrowWhenNegative(): void
    {
        $this->expectExceptionObject(new \InvalidArgumentException('Value must be a positive integer, got: -10'));
        PositiveInteger::fromInt(-10);
    }

    public function testFromOptionalString(): void
    {
        $this->assertEquals(0, PositiveInteger::fromOptionalString(0)->getValue());
        $this->assertEquals(33, PositiveInteger::fromOptionalString(33)->getValue());
        $this->assertNull(PositiveInteger::fromOptionalString(null));
        $this->assertNull(PositiveInteger::fromOptionalString(''));
    }

    public function testFromOptionalStringWhenNotNumeric(): void
    {
        $this->expectExceptionObject(new \InvalidArgumentException('Value must be an integer, got "lol"'));
        PositiveInteger::fromOptionalString('lol');
    }
}
