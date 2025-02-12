<?php

namespace App\Tests\Domain\Strava\Activity;

use App\Domain\Strava\Activity\Activity;
use App\Domain\Strava\Activity\ActivityId;
use App\Domain\Strava\Activity\ActivityIds;
use App\Domain\Strava\Activity\ActivityRepository;
use App\Domain\Strava\Activity\ActivityWithRawData;
use App\Domain\Strava\Activity\ActivityWithRawDataRepository;
use App\Domain\Strava\Activity\DbalActivityRepository;
use App\Domain\Strava\Activity\DbalActivityWithRawDataRepository;
use App\Infrastructure\Eventing\EventBus;
use App\Infrastructure\Exception\EntityNotFound;
use App\Infrastructure\ValueObject\Time\SerializableDateTime;
use App\Tests\ContainerTestCase;
use PHPUnit\Framework\MockObject\MockObject;
use Spatie\Snapshots\MatchesSnapshots;

class DbalActivityRepositoryTest extends ContainerTestCase
{
    use MatchesSnapshots;

    private ActivityRepository $activityRepository;
    private ActivityWithRawDataRepository $activityWithRawDataRepository;
    private MockObject $eventBus;

    public function testItShouldSaveAndFind(): void
    {
        $activity = ActivityBuilder::fromDefaults()->build();

        $this->activityWithRawDataRepository->save(ActivityWithRawData::fromState(
            $activity,
            ['raw' => 'data']
        ));

        $persisted = $this->activityRepository->find($activity->getId());
        $this->assertEquals(
            $activity,
            $persisted,
        );
    }

    public function testItShouldThrowWhenNotFound(): void
    {
        $this->expectException(EntityNotFound::class);
        $this->activityRepository->find(ActivityId::fromUnprefixed(1));
    }

    public function testFindAll(): void
    {
        $activityOne = ActivityBuilder::fromDefaults()
            ->withActivityId(ActivityId::fromUnprefixed(1))
            ->withStartDateTime(SerializableDateTime::fromString('2023-10-10 14:00:34'))
            ->build();
        $this->activityWithRawDataRepository->save(ActivityWithRawData::fromState(
            $activityOne,
            ['raw' => 'data']
        ));
        $activityTwo = ActivityBuilder::fromDefaults()
            ->withActivityId(ActivityId::fromUnprefixed(2))
            ->withStartDateTime(SerializableDateTime::fromString('2023-10-10 13:00:34'))
            ->build();
        $this->activityWithRawDataRepository->save(ActivityWithRawData::fromState(
            $activityTwo,
            ['raw' => 'data']
        ));
        $activityThree = ActivityBuilder::fromDefaults()
            ->withActivityId(ActivityId::fromUnprefixed(3))
            ->withStartDateTime(SerializableDateTime::fromString('2023-10-09 14:00:34'))
            ->build();
        $this->activityWithRawDataRepository->save(ActivityWithRawData::fromState(
            $activityThree,
            ['raw' => 'data']
        ));

        $this->assertEquals(
            [$activityOne->getId(), $activityTwo->getId(), $activityThree->getId()],
            $this->activityRepository->findAll()->map(fn (Activity $activity) => $activity->getId())
        );
    }

    public function testDelete(): void
    {
        $activity = ActivityBuilder::fromDefaults()->build();
        $this->activityWithRawDataRepository->save(ActivityWithRawData::fromState(
            $activity,
            ['raw' => 'data']
        ));

        $this->assertEquals(
            1,
            $this->getConnection()
                ->executeQuery('SELECT COUNT(*) FROM Activity')->fetchOne()
        );

        $this->activityRepository->delete($activity);

        $this->assertEquals(
            0,
            $this->getConnection()
                ->executeQuery('SELECT COUNT(*) FROM Activity')->fetchOne()
        );
    }

    public function testFindActivityIds(): void
    {
        $activityOne = ActivityBuilder::fromDefaults()
            ->withActivityId(ActivityId::fromUnprefixed(1))
            ->withStartDateTime(SerializableDateTime::fromString('2023-10-10 14:00:34'))
            ->build();
        $this->activityWithRawDataRepository->save(ActivityWithRawData::fromState(
            $activityOne,
            ['raw' => 'data']
        ));
        $activityTwo = ActivityBuilder::fromDefaults()
            ->withActivityId(ActivityId::fromUnprefixed(2))
            ->withStartDateTime(SerializableDateTime::fromString('2023-10-10 13:00:34'))
            ->build();
        $this->activityWithRawDataRepository->save(ActivityWithRawData::fromState(
            $activityTwo,
            ['raw' => 'data']
        ));
        $activityThree = ActivityBuilder::fromDefaults()
            ->withActivityId(ActivityId::fromUnprefixed(3))
            ->withStartDateTime(SerializableDateTime::fromString('2023-10-09 14:00:34'))
            ->build();
        $this->activityWithRawDataRepository->save(ActivityWithRawData::fromState(
            $activityThree,
            ['raw' => 'data']
        ));

        $this->assertEquals(
            ActivityIds::fromArray([
                ActivityId::fromUnprefixed(1),
                ActivityId::fromUnprefixed(2),
                ActivityId::fromUnprefixed(3),
            ]),
            $this->activityRepository->findActivityIds()
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
        $this->activityWithRawDataRepository = new DbalActivityWithRawDataRepository(
            $this->getConnection(),
            $this->activityRepository
        );
    }
}
