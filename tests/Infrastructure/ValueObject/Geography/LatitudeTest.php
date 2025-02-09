<?php

namespace App\Tests\Infrastructure\ValueObject\Geography;

use App\Infrastructure\ValueObject\Geography\Latitude;
use PHPUnit\Framework\TestCase;

class LatitudeTest extends TestCase
{
    public function testItShouldThrowWhenInvalidLatitude(): void
    {
        $this->expectExceptionObject(new \InvalidArgumentException('Invalid latitude value: 91'));

        Latitude::fromString('91');
    }
}
