<?php

namespace App\Tests\Domain\Strava\Activity\ReadModel;

use App\Domain\Strava\Activity\ActivityId;
use App\Domain\Strava\Activity\ReadModel\ActivityDetails;
use App\Domain\Strava\Activity\ReadModel\ActivityDetailsRepository;
use App\Domain\Strava\Activity\ReadModel\DbalActivityDetailsRepository;
use App\Domain\Strava\Activity\WriteModel\ActivityRepository;
use App\Domain\Strava\Activity\WriteModel\DbalActivityRepository;
use App\Infrastructure\Eventing\EventBus;
use App\Infrastructure\ValueObject\Time\SerializableDateTime;
use App\Tests\ContainerTestCase;
use App\Tests\Domain\Strava\Activity\WriteModel\ActivityBuilder;

class DbalActivityDetailsRepositoryTest extends ContainerTestCase
{
    private ActivityRepository $activityRepository;
    private ActivityDetailsRepository $activityDetailsRepository;

    public function testFindAll(): void
    {
        $activityOne = ActivityBuilder::fromDefaults()
            ->withActivityId(ActivityId::fromUnprefixed(1))
            ->withStartDateTime(SerializableDateTime::fromString('2023-10-10 14:00:34'))
            ->build();
        $this->activityRepository->add($activityOne);
        $activityTwo = ActivityBuilder::fromDefaults()
            ->withActivityId(ActivityId::fromUnprefixed(2))
            ->withStartDateTime(SerializableDateTime::fromString('2023-10-10 13:00:34'))
            ->build();
        $this->activityRepository->add($activityTwo);
        $activityThree = ActivityBuilder::fromDefaults()
            ->withActivityId(ActivityId::fromUnprefixed(3))
            ->withStartDateTime(SerializableDateTime::fromString('2023-10-09 14:00:34'))
            ->build();
        $this->activityRepository->add($activityThree);

        $this->assertEquals(
            [$activityOne->getId(), $activityTwo->getId(), $activityThree->getId()],
            $this->activityDetailsRepository->findAll()->map(fn (ActivityDetails $activityDetails) => $activityDetails->getId())
        );
    }

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->eventBus = $this->createMock(EventBus::class);

        $this->activityRepository = new DbalActivityRepository(
            $this->getConnection(),
            $this->eventBus
        );
        $this->activityDetailsRepository = new DbalActivityDetailsRepository($this->getConnection());
    }
}
