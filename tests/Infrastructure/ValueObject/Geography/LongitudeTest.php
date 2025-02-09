<?php

namespace App\Tests\Infrastructure\ValueObject\Geography;

use App\Infrastructure\ValueObject\Geography\Longitude;
use PHPUnit\Framework\TestCase;

class LongitudeTest extends TestCase
{
    public function testItShouldThrowWhenInvalidLongitude(): void
    {
        $this->expectExceptionObject(new \InvalidArgumentException('Invalid longitude value: 181'));

        Longitude::fromString('181');
    }
}
