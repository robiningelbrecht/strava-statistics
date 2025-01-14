<?php

namespace App\Tests\Domain\Strava\Activity;

use App\Domain\Strava\Activity\ActivityId;
use App\Domain\Strava\Activity\ActivityIds;
use App\Domain\Strava\Activity\WriteModel\Activities;
use App\Domain\Strava\Activity\WriteModel\ActivityRepository;
use App\Domain\Strava\Activity\WriteModel\DbalActivityRepository;
use App\Domain\Strava\Gear\GearId;
use App\Domain\Strava\Gear\GearIds;
use App\Infrastructure\Eventing\EventBus;
use App\Infrastructure\Exception\EntityNotFound;
use App\Infrastructure\ValueObject\Time\SerializableDateTime;
use App\Tests\ContainerTestCase;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use PHPUnit\Framework\MockObject\MockObject;
use Spatie\Snapshots\MatchesSnapshots;

class DbalActivityRepositoryTest extends ContainerTestCase
{
    use MatchesSnapshots;

    private ActivityRepository $activityRepository;
    private MockObject $eventBus;

    public function testItShouldSaveAndFind(): void
    {
        $activity = ActivityBuilder::fromDefaults()->build();

        $this->activityRepository->add($activity);

        $this->assertMatchesJsonSnapshot(
            $this->getConnection()
                ->executeQuery('SELECT * FROM Activity')->fetchAllAssociative()
        );
    }

    public function testItShouldThrowOnDuplicateActivity(): void
    {
        $activity = ActivityBuilder::fromDefaults()->build();

        $this->expectException(UniqueConstraintViolationException::class);

        $this->activityRepository->add($activity);
        $this->activityRepository->add($activity);
    }

    public function testUpdate(): void
    {
        $activity = ActivityBuilder::fromDefaults()
            ->withActivityId(ActivityId::fromUnprefixed(1))
            ->withStartDateTime(SerializableDateTime::fromString('2023-10-10 14:00:34'))
            ->withGearId(GearId::fromUnprefixed('1'))
            ->build();
        $this->activityRepository->add($activity);

        $this->assertEquals(
            1,
            $activity->getKudoCount()
        );

        $activity->updateKudoCount(3);
        $activity->updateGearId(GearId::fromUnprefixed('10'));
        $this->activityRepository->update($activity);

        $this->assertMatchesJsonSnapshot(
            $this->getConnection()
                ->executeQuery('SELECT * FROM Activity')->fetchAllAssociative()
        );
    }

    public function testDelete(): void
    {
        $activity = ActivityBuilder::fromDefaults()->build();
        $this->activityRepository->add($activity);

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

    public function testFind(): void
    {
        $activity = ActivityBuilder::fromDefaults()->build();
        $this->activityRepository->add($activity);

        $this->assertEquals(
            $activity,
            $this->activityRepository->find($activity->getId())
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
            Activities::fromArray([$activityOne, $activityTwo, $activityThree]),
            $this->activityRepository->findAll()
        );
    }

    public function testFindActivityIds(): void
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
            ActivityIds::fromArray([
                ActivityId::fromUnprefixed(1),
                ActivityId::fromUnprefixed(2),
                ActivityId::fromUnprefixed(3),
            ]),
            $this->activityRepository->findActivityIds()
        );
    }

    public function testFindUniqueGearIds(): void
    {
        $activityOne = ActivityBuilder::fromDefaults()
            ->withActivityId(ActivityId::fromUnprefixed(1))
            ->withGearId(GearId::fromUnprefixed(1))
            ->withStartDateTime(SerializableDateTime::fromString('2023-10-10 14:00:34'))
            ->build();
        $this->activityRepository->add($activityOne);
        $activityTwo = ActivityBuilder::fromDefaults()
            ->withActivityId(ActivityId::fromUnprefixed(2))
            ->withGearId(GearId::fromUnprefixed(2))
            ->withStartDateTime(SerializableDateTime::fromString('2023-10-10 13:00:34'))
            ->build();
        $this->activityRepository->add($activityTwo);
        $activityThree = ActivityBuilder::fromDefaults()
            ->withActivityId(ActivityId::fromUnprefixed(3))
            ->withGearId(GearId::fromUnprefixed(1))
            ->withStartDateTime(SerializableDateTime::fromString('2023-10-09 14:00:34'))
            ->build();
        $this->activityRepository->add($activityThree);
        $this->activityRepository->add(ActivityBuilder::fromDefaults()
            ->withActivityId(ActivityId::fromUnprefixed(4))
            ->withoutGearId()
            ->withStartDateTime(SerializableDateTime::fromString('2023-10-09 14:00:34'))
            ->build());

        $this->assertEquals(
            GearIds::fromArray([GearId::fromUnprefixed(1), GearId::fromUnprefixed(2)]),
            $this->activityRepository->findUniqueGearIds()
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
    }
}
