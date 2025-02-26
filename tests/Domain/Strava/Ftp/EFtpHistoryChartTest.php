<?php

namespace App\Tests\Domain\Strava\Ftp;

use App\Domain\Strava\Activity\ActivityType;
use App\Domain\Strava\Activity\SportType\SportType;
use App\Domain\Strava\Ftp\EFtpHistoryChart;
use App\Domain\Strava\Ftp\InMemoryEFtpRepository;
use App\Infrastructure\ValueObject\Time\SerializableDateTime;
use App\Tests\Domain\Strava\Activity\ActivityBuilder;
use PHPUnit\Framework\TestCase;

class EFtpHistoryChartTest extends TestCase
{
    private InMemoryEFtpRepository $eftpRepository;

    public function testEmptyData(): void
    {
        $emptyRepository = InMemoryEFtpRepository::from(3);

        $chartData = EFtpHistoryChart::create(
            repository: $emptyRepository,
            activityType: ActivityType::RIDE,
            now: SerializableDateTime::fromString('2023-04-24'),
        )->build();

        $this->assertEquals($chartData, []);
        $this->assertEquals(count($chartData), 0);
    }

    public function testRideChart(): void
    {
        $chartData = EFtpHistoryChart::create(
            repository: $this->eftpRepository,
            activityType: ActivityType::RIDE,
            now: SerializableDateTime::fromString('2023-04-24'),
        )->build();

        $this->assertEquals(count($chartData) > 1, true);
    }

    public function testRunChart(): void
    {
        $chartData = EFtpHistoryChart::create(
            repository: $this->eftpRepository,
            activityType: ActivityType::RUN,
            now: SerializableDateTime::fromString('2023-04-24'),
        )->build();

        $this->assertEquals(count($chartData) > 1, true);
    }

    public function testWalkChart(): void
    {
        $chartData = EFtpHistoryChart::create(
            repository: $this->eftpRepository,
            activityType: ActivityType::WALK,
            now: SerializableDateTime::fromString('2023-04-24'),
        )->build();

        $this->assertEquals(count($chartData), 0);
    }

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->eftpRepository = EFtpRepositoryBuilder::fromDefaults()
            ->withActivityAndPower(
                ActivityBuilder::fromDefaults()
                    ->withStartDateTime(SerializableDateTime::fromString('2023-01-01'))
                    ->withSportType(SportType::RIDE)
                    ->build(), 200
            )
            ->withActivityAndPower(
                ActivityBuilder::fromDefaults()
                    ->withStartDateTime(SerializableDateTime::fromString('2023-01-10'))
                    ->withSportType(SportType::RIDE)
                    ->build(), 300
            )
            ->withActivityAndPower(
                ActivityBuilder::fromDefaults()
                    ->withStartDateTime(SerializableDateTime::fromString('2023-01-31'))
                    ->withSportType(SportType::RUN)
                    ->build(), 100
            )
            ->withNumberOfMonths(3)
            ->build();
    }
}
