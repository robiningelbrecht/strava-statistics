<?php

namespace App\Tests\Domain\Strava\EFtp;

use App\Domain\Strava\Activity\ActivityType;
use App\Domain\Strava\Activity\SportType\SportType;
use App\Domain\Strava\Activity\Stream\ActivityPowerRepository;
use App\Domain\Strava\Activity\Stream\PowerOutput;
use App\Domain\Strava\Activity\Stream\PowerOutputs;
use App\Domain\Strava\EFtp\EFtpCalculator;
use App\Domain\Strava\EFtp\EFtpNumberOfMonths;
use App\Infrastructure\ValueObject\Time\SerializableDateTime;
use App\Tests\Domain\Strava\Activity\ActivityBuilder;
use PHPUnit\Framework\TestCase;

class EFtpCalculatorTest extends TestCase
{
    private EFtpCalculator $eftpCalculator;

    public function testCalculateWithoutPower(): void
    {
        $activity = ActivityBuilder::fromDefaults()
            ->withStartDateTime(SerializableDateTime::fromString('2023-01-31'))
            ->withSportType(SportType::RUN)
            ->build();

        $eftp = $this->eftpCalculator->calculate($activity);

        $this->assertNull($eftp);
    }

    public function testFactorIntervalsAreSet(): void
    {
        $factorIntervals = array_keys(EFtpCalculator::EFTP_FACTORS);
        $calculatedIntervals = ActivityPowerRepository::TIME_INTERVALS_IN_SECONDS_REDACTED;

        foreach ($factorIntervals as $interval) {
            $this->assertContains($interval, $calculatedIntervals);
        }
    }

    public function testCalculateWithPower(): void
    {
        $activity = ActivityBuilder::fromDefaults()
            ->withStartDateTime(SerializableDateTime::fromString('2023-01-31'))
            ->withSportType(SportType::RUN)
            ->build();

        $power = PowerOutput::fromState(
            formattedTimeInterval: '20 min',
            timeIntervalInSeconds: 1200,
            power: 300,
            relativePower: 0,
        );
        $powerOutputs = PowerOutputs::fromArray([$power]);

        $activity->enrichWithBestPowerOutputs($powerOutputs);

        $eftp = $this->eftpCalculator->calculate($activity);

        $this->assertEquals($eftp->getPower(), 285);
        $this->assertEquals($eftp->getRelativePower(), 3.56);
    }

    public function testEmptyRepository(): void
    {
        $emptyCalculator = new EFtpCalculator(EFtpAthleteWeightRepository::fromWeightInKg(80), EFtpNumberOfMonths::from(3));

        $date = SerializableDateTime::fromString('2023-04-24');
        $eftp = $emptyCalculator->findForActivityType(ActivityType::RIDE, $date);

        $this->assertNull($eftp);
    }

    public function testDisabled(): void
    {
        $calculator = EFtpCalculatorBuilder::fromDefaults()
            ->withWeightRepository(EFtpAthleteWeightRepository::fromWeightInKg(80))
            ->withActivityAndPower(
                ActivityBuilder::fromDefaults()
                    ->withStartDateTime(SerializableDateTime::fromString('2023-01-01'))
                    ->withSportType(SportType::RIDE)
                    ->build(), 200
            )
            ->withNumberOfMonths(0)
            ->build();

        $resultEnabled = $calculator->isEnabled();
        $resultEftp = $calculator->findForActivityType(ActivityType::RIDE, SerializableDateTime::fromString('2023-01-02'));

        $this->assertEquals($resultEnabled, false);
        $this->assertNull($resultEftp);
    }

    public function testEnabled(): void
    {
        $calculator = EFtpCalculatorBuilder::fromDefaults()
            ->withWeightRepository(EFtpAthleteWeightRepository::fromWeightInKg(80))
            ->withActivityAndPower(
                ActivityBuilder::fromDefaults()
                    ->withStartDateTime(SerializableDateTime::fromString('2023-01-01'))
                    ->withSportType(SportType::RIDE)
                    ->build(), 200
            )
            ->withNumberOfMonths(1)
            ->build();

        $resultEnabled = $calculator->isEnabled();
        $resultEftp = $calculator
            ->findForActivityType(ActivityType::RIDE, SerializableDateTime::fromString('2023-01-02'))
            ->getEFtp();

        $this->assertEquals($resultEnabled, true);
        $this->assertEquals($resultEftp, 200);
    }

    public function testMonths(): void
    {
        $calculator = EFtpCalculatorBuilder::fromDefaults()
            ->withWeightRepository(EFtpAthleteWeightRepository::fromWeightInKg(80))
            ->withActivityAndPower(
                ActivityBuilder::fromDefaults()
                    ->withStartDateTime(SerializableDateTime::fromString('2023-01-01'))
                    ->withSportType(SportType::RIDE)
                    ->build(), 200
            )
            ->withNumberOfMonths(1)
            ->build();

        $resultEftp1 = $calculator
            ->findForActivityType(ActivityType::RIDE, SerializableDateTime::fromString('2023-02-01'))
            ->getEFtp();
        $resultEftp2 = $calculator
            ->findForActivityType(ActivityType::RIDE, SerializableDateTime::fromString('2023-02-02'));

        $this->assertEquals($resultEftp1, 200);
        $this->assertNull($resultEftp2);
    }

    public function testBikeEftpNull(): void
    {
        $date = SerializableDateTime::fromString('2023-04-24');
        $eftp = $this->eftpCalculator->findForActivityType(ActivityType::RIDE, $date);

        $this->assertNull($eftp);
    }

    public function testRunEftpNull(): void
    {
        $date = SerializableDateTime::fromString('2023-01-30');
        $eftp = $this->eftpCalculator->findForActivityType(ActivityType::RUN, $date);

        $this->assertNull($eftp);
    }

    public function testRunEftp(): void
    {
        $eftp1 = $this->eftpCalculator->findForActivityType(
            ActivityType::RUN,
            SerializableDateTime::fromString('2023-01-31')
        );
        $eftp2 = $this->eftpCalculator->findForActivityType(
            ActivityType::RUN,
            SerializableDateTime::fromString('2023-05-01')
        );

        $this->assertEquals($eftp1->getEFtp(), 100);
        $this->assertNull($eftp2);
    }

    public function testBikeEftp(): void
    {
        $eftp1 = $this->eftpCalculator->findForActivityType(
            ActivityType::RIDE,
            SerializableDateTime::fromString('2023-01-09')
        );
        $eftp2 = $this->eftpCalculator->findForActivityType(
            ActivityType::RIDE,
            SerializableDateTime::fromString('2023-01-10'),
        );

        $this->assertEquals($eftp1->getEFtp(), 200);
        $this->assertEquals($eftp2->getEFtp(), 300);
    }

    public function testBikeDates(): void
    {
        $result = $this->eftpCalculator->findEFtpDates(ActivityType::RIDE);

        $this->assertEquals($result, ['2023-01-01', '2023-01-10']);
    }

    public function testRunDates(): void
    {
        $result = $this->eftpCalculator->findEFtpDates(ActivityType::RUN);

        $this->assertEquals($result, ['2023-01-31']);
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
