<?php

namespace App\Tests\Domain\Strava\Activity\SportType;

use App\Domain\Strava\Activity\ActivityId;
use App\Domain\Strava\Activity\SportType\DbalSportTypeRepository;
use App\Domain\Strava\Activity\SportType\SportType;
use App\Domain\Strava\Activity\SportType\SportTypes;
use App\Domain\Strava\Activity\SportType\SportTypesToImport;
use App\Domain\Strava\Activity\WriteModel\ActivityRepository;
use App\Tests\ContainerTestCase;
use App\Tests\Domain\Strava\Activity\ActivityBuilder;

class DbalSportTypeRepositoryTest extends ContainerTestCase
{
    public function testFindAll(): void
    {
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

        $sportTypeRepository = new DbalSportTypeRepository(
            $this->getConnection(),
            SportTypesToImport::fromArray([SportType::RUN, SportType::WALK])
        );

        $this->assertEquals(
            SportTypes::fromArray([SportType::RUN, SportType::WALK]),
            $sportTypeRepository->findAll(),
        );

        $sportTypeRepository = new DbalSportTypeRepository(
            $this->getConnection(),
            SportTypesToImport::fromArray([SportType::WALK, SportType::RUN])
        );

        $this->assertEquals(
            SportTypes::fromArray([SportType::WALK, SportType::RUN]),
            $sportTypeRepository->findAll(),
        );
    }
}
