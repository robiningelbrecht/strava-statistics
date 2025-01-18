<?php

namespace App\Tests\Domain\Strava\Activity;

use App\Domain\Strava\Activity\ActivityRepository;
use App\Domain\Strava\Activity\ActivityWithRawData;
use App\Domain\Strava\Activity\ActivityWithRawDataRepository;
use App\Domain\Strava\Activity\DbalActivityWithRawDataRepository;
use App\Tests\ContainerTestCase;

class ActivityWithRawDataRepositoryTest extends ContainerTestCase
{
    private ActivityWithRawDataRepository $activityWithRawDataRepository;

    public function testSaveAndFind(): void
    {
        $activity = ActivityWithRawData::fromState(
            ActivityBuilder::fromDefaults()->build(),
            ['raw' => 'data']
        );

        $this->activityWithRawDataRepository->save($activity);

        $persisted = $this->activityWithRawDataRepository->find($activity->getActivity()->getId());
        $this->assertEquals(
            $activity,
            $persisted,
        );
    }

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->activityWithRawDataRepository = new DbalActivityWithRawDataRepository(
            $this->getConnection(),
            $this->getContainer()->get(ActivityRepository::class)
        );
    }
}
