<?php

namespace App\Tests\Domain\Strava\Activity;

use App\Domain\Strava\Activity\ActivityId;
use App\Domain\Strava\Activity\ActivityType;
use App\Domain\Strava\Activity\ActivityTypes;
use App\Domain\Strava\Activity\SportType\DbalSportTypeRepository;
use App\Domain\Strava\Activity\SportType\SportType;
use App\Domain\Strava\Activity\SportType\SportTypesToImport;
use App\Domain\Strava\Activity\SportTypeBasedActivityTypeRepository;
use App\Domain\Strava\Activity\WriteModel\ActivityRepository;
use App\Tests\ContainerTestCase;
use App\Tests\Domain\Strava\Activity\WriteModel\ActivityBuilder;

class SportTypeBasedActivityTypeRepositoryTest extends ContainerTestCase
{
    public function testFindAll(): void
    {
        $this->getContainer()->get(ActivityRepository::class)->add(
            ActivityBuilder::fromDefaults()
                ->withActivityId(ActivityId::random())
                ->withSportType(SportType::WALK)
                ->build()
        );
        $this->getContainer()->get(ActivityRepository::class)->add(
            ActivityBuilder::fromDefaults()
                ->withActivityId(ActivityId::random())
                ->withSportType(SportType::RUN)
                ->build()
        );
        $this->getContainer()->get(ActivityRepository::class)->add(
            ActivityBuilder::fromDefaults()
                ->withActivityId(ActivityId::random())
                ->withSportType(SportType::RUN)
                ->build()
        );
        $this->getContainer()->get(ActivityRepository::class)->add(
            ActivityBuilder::fromDefaults()
                ->withActivityId(ActivityId::random())
                ->withSportType(SportType::WALK)
                ->build()
        );

        $activityTypeRepository = new SportTypeBasedActivityTypeRepository(
            new DbalSportTypeRepository(
                $this->getConnection(),
                SportTypesToImport::fromArray([SportType::RUN, SportType::WALK])
            )
        );

        $this->assertEquals(
            ActivityTypes::fromArray([ActivityType::RUN, ActivityType::WALK]),
            $activityTypeRepository->findAll(),
        );

        $activityTypeRepository = new SportTypeBasedActivityTypeRepository(
            new DbalSportTypeRepository(
                $this->getConnection(),
                SportTypesToImport::fromArray([SportType::WALK, SportType::RUN])
            )
        );

        $this->assertEquals(
            ActivityTypes::fromArray([ActivityType::WALK, ActivityType::RUN]),
            $activityTypeRepository->findAll(),
        );
    }
}
