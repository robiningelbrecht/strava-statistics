<?php

namespace App\Tests\Infrastructure\ValueObject\Time;

use App\Infrastructure\Serialization\Json;
use App\Infrastructure\ValueObject\Time\SerializableDateTime;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class SerializableDateTimeTest extends TestCase
{
    public function testFromString(): void
    {
        $this->assertEquals(
            new \DateTimeImmutable('2023-10-05 10:22:22'),
            SerializableDateTime::fromString('2023-10-05 10:22:22')
        );
    }

    public function testFromTimeStamp(): void
    {
        $this->assertEquals(
            new \DateTimeImmutable('2023-10-05 18:56:31'),
            SerializableDateTime::fromTimestamp('1696532191')
        );
    }

    public function testSerialize(): void
    {
        $date = SerializableDateTime::fromString('2023-10-05 10:22:22');
        $this->assertEquals(
            Json::encode($date),
            Json::encode((string) $date),
        );
    }

    public function testToUtc(): void
    {
        $this->assertEquals(
            SerializableDateTime::fromString('2023-10-05 10:22:22'),
            SerializableDateTime::fromString('2023-10-05 10:22:22')->toUtc(),
        );
    }

    #[DataProvider(methodName: 'provideYearAndWeekNumberDates')]
    public function testGetYearAndWeekNumberString(SerializableDateTime $date, string $expectedYearAndWeek): void
    {
        $this->assertEquals(
            $date->getYearAndWeekNumberString(),
            $expectedYearAndWeek,
        );
    }

    public static function provideYearAndWeekNumberDates(): array
    {
        return [
            [SerializableDateTime::fromString('2024-12-28'), '2024-52'],
            [SerializableDateTime::fromString('2009-12-28'), '2009-52'],
            [SerializableDateTime::fromString('2010-01-01'), '2010-1'],
        ];
    }
}
