<?php

namespace App\Tests\Domain\Strava\EFtp;

use App\Domain\Strava\Activity\ActivityType;
use App\Domain\Strava\Activity\SportType\SportType;
use App\Domain\Strava\EFtp\EFtpCalculator;
use App\Domain\Strava\EFtp\EFtpHistoryChart;
use App\Domain\Strava\EFtp\EFtpNumberOfMonths;
use App\Infrastructure\ValueObject\Time\SerializableDateTime;
use App\Tests\Domain\Strava\Activity\ActivityBuilder;
use PHPUnit\Framework\TestCase;

class EFtpHistoryChartTest extends TestCase
{
    private EFtpCalculator $eftpCalculator;

    public function testEmptyData(): void
    {
        $emptyCalculator = new EFtpCalculator(EFtpAthleteWeightRepository::fromWeightInKg(80), EFtpNumberOfMonths::from(3));

        $chartData = EFtpHistoryChart::create(
            eftpCalculator: $emptyCalculator,
            activityType: ActivityType::RIDE,
            now: SerializableDateTime::fromString('2023-04-24'),
        )->build();

        $this->assertEquals($chartData, []);
        $this->assertEquals(count($chartData), 0);
    }

    public function testRideChart(): void
    {
        $chartData = EFtpHistoryChart::create(
            eftpCalculator: $this->eftpCalculator,
            activityType: ActivityType::RIDE,
            now: SerializableDateTime::fromString('2023-04-24'),
        )->build();

        $this->assertEquals(count($chartData) > 1, true);
    }

    public function testRunChart(): void
    {
        $chartData = EFtpHistoryChart::create(
            eftpCalculator: $this->eftpCalculator,
            activityType: ActivityType::RUN,
            now: SerializableDateTime::fromString('2023-04-24'),
        )->build();

        $this->assertEquals(count($chartData) > 1, true);
    }

    public function testWalkChart(): void
    {
        $chartData = EFtpHistoryChart::create(
            eftpCalculator: $this->eftpCalculator,
            activityType: ActivityType::WALK,
            now: SerializableDateTime::fromString('2023-04-24'),
        )->build();

        $this->assertEquals(count($chartData), 0);
    }

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $athleteWeightRepository = EFtpAthleteWeightRepository::fromWeightInKg(80);

        $this->eftpCalculator = EFtpCalculatorBuilder::fromDefaults()
            ->withWeightRepository($athleteWeightRepository)
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
