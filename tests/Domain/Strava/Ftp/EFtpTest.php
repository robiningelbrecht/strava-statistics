<?php

namespace App\Tests\Domain\Strava\Ftp;

use App\Domain\Strava\Activity\ActivityType;
use App\Domain\Strava\Activity\SportType\SportType;
use App\Domain\Strava\Ftp\InMemoryEFtpRepository;
use App\Infrastructure\ValueObject\Time\SerializableDateTime;
use App\Tests\Domain\Strava\Activity\ActivityBuilder;
use PHPUnit\Framework\TestCase;

class InMemoryEFtpTest extends TestCase
{
    private InMemoryEFtpRepository $eftpRepository;

    public function testEmptyRepository(): void
    {
        $emptyRepository = InMemoryEFtpRepository::from(3);

        $date = SerializableDateTime::fromString('2023-04-24');
        $eftp = $emptyRepository->findForActivityType(ActivityType::RIDE, $date);

        $this->assertNull($eftp);
    }

    public function testDisabled(): void
    {
        $repository = EFtpRepositoryBuilder::fromDefaults()
            ->withActivityAndPower(
                ActivityBuilder::fromDefaults()
                    ->withStartDateTime(SerializableDateTime::fromString('2023-01-01'))
                    ->withSportType(SportType::RIDE)
                    ->build(), 200
            )
            ->withNumberOfMonths(0)
            ->build();

        $resultEnabled = $repository->enabled();
        $resultEftp = $repository->findForActivityType(ActivityType::RIDE, SerializableDateTime::fromString('2023-01-02'));

        $this->assertEquals($resultEnabled, false);
        $this->assertNull($resultEftp);
    }

    public function testEnabled(): void
    {
        $repository = EFtpRepositoryBuilder::fromDefaults()
            ->withActivityAndPower(
                ActivityBuilder::fromDefaults()
                    ->withStartDateTime(SerializableDateTime::fromString('2023-01-01'))
                    ->withSportType(SportType::RIDE)
                    ->build(), 200
            )
            ->withNumberOfMonths(1)
            ->build();

        $resultEnabled = $repository->enabled();
        $resultEftp = $repository
            ->findForActivityType(ActivityType::RIDE, SerializableDateTime::fromString('2023-01-02'))
            ->getEftp();

        $this->assertEquals($resultEnabled, true);
        $this->assertEquals($resultEftp, 200);
    }

    public function testMonths(): void
    {
        $repository = EFtpRepositoryBuilder::fromDefaults()
            ->withActivityAndPower(
                ActivityBuilder::fromDefaults()
                    ->withStartDateTime(SerializableDateTime::fromString('2023-01-01'))
                    ->withSportType(SportType::RIDE)
                    ->build(), 200
            )
            ->withNumberOfMonths(1)
            ->build();

        $resultEftp1 = $repository
            ->findForActivityType(ActivityType::RIDE, SerializableDateTime::fromString('2023-02-01'))
            ->getEftp();
        $resultEftp2 = $repository
            ->findForActivityType(ActivityType::RIDE, SerializableDateTime::fromString('2023-02-02'));

        $this->assertEquals($resultEftp1, 200);
        $this->assertNull($resultEftp2);
    }

    public function testBikeEftpNull(): void
    {
        $date = SerializableDateTime::fromString('2023-04-24');
        $eftp = $this->eftpRepository->findForActivityType(ActivityType::RIDE, $date);

        $this->assertNull($eftp);
    }

    public function testRunEftpNull(): void
    {
        $date = SerializableDateTime::fromString('2023-01-30');
        $eftp = $this->eftpRepository->findForActivityType(ActivityType::RUN, $date);

        $this->assertNull($eftp);
    }

    public function testRunEftp(): void
    {
        $eftp1 = $this->eftpRepository->findForActivityType(
            ActivityType::RUN,
            SerializableDateTime::fromString('2023-01-31')
        );
        $eftp2 = $this->eftpRepository->findForActivityType(
            ActivityType::RUN,
            SerializableDateTime::fromString('2023-05-01')
        );

        $this->assertEquals($eftp1->getEftp(), 100);
        $this->assertNull($eftp2);
    }

    public function testBikeEftp(): void
    {
        $eftp1 = $this->eftpRepository->findForActivityType(
            ActivityType::RIDE,
            SerializableDateTime::fromString('2023-01-09')
        );
        $eftp2 = $this->eftpRepository->findForActivityType(
            ActivityType::RIDE,
            SerializableDateTime::fromString('2023-01-10'),
        );

        $this->assertEquals($eftp1->getEftp(), 200);
        $this->assertEquals($eftp2->getEftp(), 300);
    }

    public function testBikeDates(): void
    {
        $result = $this->eftpRepository->findEFtpDates(ActivityType::RIDE);

        $this->assertEquals($result, ['2023-01-01', '2023-01-10']);
    }

    public function testRunDates(): void
    {
        $result = $this->eftpRepository->findEFtpDates(ActivityType::RUN);

        $this->assertEquals($result, ['2023-01-31']);
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
