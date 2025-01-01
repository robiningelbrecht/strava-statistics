<?php

namespace App\Tests\Domain\Strava\Activity;

use App\Domain\Strava\Activity\ActivityIntensity;
use App\Domain\Strava\Athlete\Athlete;
use App\Domain\Strava\Ftp\DbalFtpRepository;
use App\Domain\Strava\Ftp\FtpRepository;
use App\Domain\Strava\Ftp\FtpValue;
use App\Infrastructure\ValueObject\Time\SerializableDateTime;
use App\Tests\ContainerTestCase;
use App\Tests\Domain\Strava\Ftp\FtpBuilder;

class ActivityIntensityTest extends ContainerTestCase
{
    private ActivityIntensity $activityIntensity;
    private FtpRepository $ftpRepository;

    public function testCalculateWithFtp(): void
    {
        $ftp = FtpBuilder::fromDefaults()
            ->withSetOn(SerializableDateTime::fromString('2023-04-01'))
            ->withFtp(FtpValue::fromInt(250))
            ->build();
        $this->ftpRepository->save($ftp);

        $activity = ActivityBuilder::fromDefaults()
            ->withData([
                'average_watts' => 250,
                'moving_time' => 3600,
            ])
            ->build();

        $this->assertEquals(
            100,
            $this->activityIntensity->calculate($activity),
        );
    }

    public function testCalculateWithHeartRate(): void
    {
        $activity = ActivityBuilder::fromDefaults()
            ->withData([
                'average_heartrate' => 171,
                'moving_time' => 3600,
            ])
            ->build();

        $this->assertEquals(
            100,
            $this->activityIntensity->calculate($activity),
        );
    }

    public function testCalculateShouldBeNull(): void
    {
        $activity = ActivityBuilder::fromDefaults()
            ->withData([
                'moving_time' => 3600,
            ])
            ->build();

        $this->assertNull(
            $this->activityIntensity->calculate($activity),
        );
    }

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->ftpRepository = new DbalFtpRepository(
            $this->getConnection()
        );

        $this->activityIntensity = new ActivityIntensity(
            Athlete::create(SerializableDateTime::fromString('1989-08-14')),
            $this->ftpRepository
        );
    }
}
