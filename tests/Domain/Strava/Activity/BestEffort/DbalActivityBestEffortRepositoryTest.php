<?php

namespace App\Tests\Domain\Strava\Activity\BestEffort;

use App\Domain\Strava\Activity\ActivityId;
use App\Domain\Strava\Activity\ActivityIds;
use App\Domain\Strava\Activity\ActivityWithRawData;
use App\Domain\Strava\Activity\ActivityWithRawDataRepository;
use App\Domain\Strava\Activity\BestEffort\ActivityBestEffort;
use App\Domain\Strava\Activity\BestEffort\ActivityBestEffortRepository;
use App\Domain\Strava\Activity\BestEffort\DbalActivityBestEffortRepository;
use App\Domain\Strava\Activity\SportType\SportType;
use App\Infrastructure\Serialization\Json;
use App\Infrastructure\ValueObject\Measurement\Length\Meter;
use App\Tests\ContainerTestCase;
use App\Tests\Domain\Strava\Activity\ActivityBuilder;
use Spatie\Snapshots\MatchesSnapshots;

class DbalActivityBestEffortRepositoryTest extends ContainerTestCase
{
    use MatchesSnapshots;

    private ActivityBestEffortRepository $activityBestEffortRepository;

    public function testAdd(): void
    {
        $this->activityBestEffortRepository->add(
            ActivityBestEffortBuilder::fromDefaults()
                ->withActivityId(ActivityId::fromUnprefixed('test'))
                ->withSportType(SportType::RIDE)
                ->withDistanceInMeter(Meter::from(10000))
                ->withTimeInSeconds(3600)
                ->build()
        );
        $this->activityBestEffortRepository->add(
            ActivityBestEffortBuilder::fromDefaults()
                ->withActivityId(ActivityId::fromUnprefixed('test-2'))
                ->withSportType(SportType::RIDE)
                ->withDistanceInMeter(Meter::from(10000))
                ->withTimeInSeconds(3600)
                ->build()
        );
        $this->activityBestEffortRepository->add(
            ActivityBestEffortBuilder::fromDefaults()
                ->withActivityId(ActivityId::fromUnprefixed('test-2'))
                ->withSportType(SportType::RIDE)
                ->withDistanceInMeter(Meter::from(1000))
                ->withTimeInSeconds(3600)
                ->build()
        );

        $this->assertMatchesJsonSnapshot(Json::encode(
            $this->getConnection()->executeQuery('SELECT * FROM ActivityBestEffort')->fetchAllAssociative()
        ));
    }

    public function testFindBestEffortsFor(): void
    {
        foreach ([SportType::RIDE, SportType::GRAVEL_RIDE, SportType::RUN] as $sportType) {
            foreach ($sportType->getDistancesForBestEffortCalculation() as $distance) {
                for ($i = 10; $i > 0; --$i) {
                    $this->activityBestEffortRepository->add(
                        ActivityBestEffortBuilder::fromDefaults()
                            ->withActivityId(ActivityId::fromUnprefixed($sportType->value.'-'.$distance->toInt().'-'.$i))
                            ->withSportType($sportType)
                            ->withDistanceInMeter($distance)
                            ->withTimeInSeconds($i)
                            ->build()
                    );
                }
            }
        }

        foreach ([SportType::RIDE, SportType::GRAVEL_RIDE, SportType::RUN] as $sportType) {
            $this->assertMatchesJsonSnapshot(Json::encode(
                $this->activityBestEffortRepository->findBestEffortsFor($sportType)->map(
                    fn (ActivityBestEffort $bestEffort) => [
                        'activityId' => $bestEffort->getActivityId(),
                        'sportType' => $bestEffort->getSportType()->value,
                        'distanceInMeter' => $bestEffort->getDistanceInMeter()->toInt(),
                        'timeInSeconds' => $bestEffort->getTimeInSeconds(),
                    ]
                )
            ));
        }

        $this->assertEmpty(
            $this->activityBestEffortRepository->findBestEffortsFor(SportType::MOUNTAIN_BIKE_RIDE)
        );
    }

    public function testFindActivityIdsWithoutBestEfforts(): void
    {
        $this->activityBestEffortRepository->add(
            ActivityBestEffortBuilder::fromDefaults()
                ->withActivityId(ActivityId::fromUnprefixed('test'))
                ->withSportType(SportType::RIDE)
                ->withDistanceInMeter(Meter::from(10000))
                ->withTimeInSeconds(3600)
                ->build()
        );
        $this->activityBestEffortRepository->add(
            ActivityBestEffortBuilder::fromDefaults()
                ->withActivityId(ActivityId::fromUnprefixed('test-2'))
                ->withSportType(SportType::RIDE)
                ->withDistanceInMeter(Meter::from(10000))
                ->withTimeInSeconds(3600)
                ->build()
        );

        $this->getContainer()->get(ActivityWithRawDataRepository::class)->add(
            ActivityWithRawData::fromState(
                ActivityBuilder::fromDefaults()
                    ->withActivityId(ActivityId::fromUnprefixed('test-2'))
                    ->build(),
                []
            )
        );

        $this->getContainer()->get(ActivityWithRawDataRepository::class)->add(
            ActivityWithRawData::fromState(
                ActivityBuilder::fromDefaults()
                    ->withActivityId(ActivityId::fromUnprefixed('test-4'))
                    ->build(),
                []
            )
        );

        $this->assertEquals(
            ActivityIds::fromArray([ActivityId::fromUnprefixed('test-4')]),
            $this->activityBestEffortRepository->findActivityIdsWithoutBestEfforts()
        );
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->activityBestEffortRepository = new DbalActivityBestEffortRepository(
            $this->getConnection()
        );
    }
}
