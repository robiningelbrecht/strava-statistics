<?php

namespace App\Tests\Domain\Strava\Segment\SegmentEffort;

use App\Domain\Strava\Activity\ActivityId;
use App\Domain\Strava\Segment\SegmentEffort\DbalSegmentEffortRepository;
use App\Domain\Strava\Segment\SegmentEffort\SegmentEffortId;
use App\Domain\Strava\Segment\SegmentEffort\SegmentEffortRepository;
use App\Domain\Strava\Segment\SegmentEffort\SegmentEfforts;
use App\Domain\Strava\Segment\SegmentId;
use App\Infrastructure\Exception\EntityNotFound;
use App\Tests\ContainerTestCase;

class DbalSegmentEffortRepositoryTest extends ContainerTestCase
{
    private SegmentEffortRepository $segmentEffortRepository;

    public function testFindAndSave(): void
    {
        $segmentEffort = SegmentEffortBuilder::fromDefaults()
            ->build();
        $this->segmentEffortRepository->add($segmentEffort);

        $this->assertEquals(
            $segmentEffort,
            $this->segmentEffortRepository->find($segmentEffort->getId())
        );
    }

    public function testUpdate(): void
    {
        $segmentEffort = SegmentEffortBuilder::fromDefaults()
            ->build();
        $this->segmentEffortRepository->add($segmentEffort);
        $segmentEffort = SegmentEffortBuilder::fromDefaults()
            ->withData(['segment' => 'lol'])
            ->build();
        $this->segmentEffortRepository->update($segmentEffort);

        $this->assertEquals(
            SegmentEffortBuilder::fromDefaults()->build(),
            $this->segmentEffortRepository->find($segmentEffort->getId())
        );
    }

    public function testItShouldThrowWhenNotFound(): void
    {
        $this->expectException(EntityNotFound::class);
        $this->segmentEffortRepository->find(SegmentEffortId::fromUnprefixed(1));
    }

    public function testFindBySegmentId(): void
    {
        $segmentEffortOne = SegmentEffortBuilder::fromDefaults()
            ->withId(SegmentEffortId::fromUnprefixed(1))
            ->withSegmentId(SegmentId::fromUnprefixed(1))
            ->build();
        $this->segmentEffortRepository->add($segmentEffortOne);

        $segmentEffortTwo = SegmentEffortBuilder::fromDefaults()
            ->withId(SegmentEffortId::fromUnprefixed(2))
            ->withSegmentId(SegmentId::fromUnprefixed(1))
            ->build();
        $this->segmentEffortRepository->add($segmentEffortTwo);

        $segmentEffortThree = SegmentEffortBuilder::fromDefaults()
            ->withId(SegmentEffortId::fromUnprefixed(3))
            ->withSegmentId(SegmentId::fromUnprefixed(2))
            ->build();
        $this->segmentEffortRepository->add($segmentEffortThree);

        $this->assertEquals(
            SegmentEfforts::fromArray([$segmentEffortOne, $segmentEffortTwo]),
            $this->segmentEffortRepository->findBySegmentId($segmentEffortOne->getSegmentId(), 10)
        );
    }

    public function testCountBySegmentId(): void
    {
        $segmentEffortOne = SegmentEffortBuilder::fromDefaults()
            ->withId(SegmentEffortId::fromUnprefixed(1))
            ->withSegmentId(SegmentId::fromUnprefixed(1))
            ->build();
        $this->segmentEffortRepository->add($segmentEffortOne);

        $segmentEffortTwo = SegmentEffortBuilder::fromDefaults()
            ->withId(SegmentEffortId::fromUnprefixed(2))
            ->withSegmentId(SegmentId::fromUnprefixed(1))
            ->build();
        $this->segmentEffortRepository->add($segmentEffortTwo);

        $segmentEffortThree = SegmentEffortBuilder::fromDefaults()
            ->withId(SegmentEffortId::fromUnprefixed(3))
            ->withSegmentId(SegmentId::fromUnprefixed(2))
            ->build();
        $this->segmentEffortRepository->add($segmentEffortThree);

        $this->assertEquals(
            2,
            $this->segmentEffortRepository->countBySegmentId($segmentEffortOne->getSegmentId())
        );
    }

    public function testFindByActivityId(): void
    {
        $segmentEffortOne = SegmentEffortBuilder::fromDefaults()
            ->withId(SegmentEffortId::fromUnprefixed(1))
            ->withActivityId(ActivityId::fromUnprefixed(1))
            ->build();
        $this->segmentEffortRepository->add($segmentEffortOne);

        $segmentEffortTwo = SegmentEffortBuilder::fromDefaults()
            ->withId(SegmentEffortId::fromUnprefixed(2))
            ->withActivityId(ActivityId::fromUnprefixed(1))
            ->build();
        $this->segmentEffortRepository->add($segmentEffortTwo);

        $segmentEffortThree = SegmentEffortBuilder::fromDefaults()
            ->withId(SegmentEffortId::fromUnprefixed(3))
            ->withActivityId(ActivityId::fromUnprefixed(2))
            ->build();
        $this->segmentEffortRepository->add($segmentEffortThree);

        $this->assertEquals(
            SegmentEfforts::fromArray([$segmentEffortOne, $segmentEffortTwo]),
            $this->segmentEffortRepository->findByActivityId($segmentEffortOne->getActivityId())
        );
    }

    public function testDelete(): void
    {
        $segmentEffortOne = SegmentEffortBuilder::fromDefaults()->build();
        $this->segmentEffortRepository->add($segmentEffortOne);

        $this->assertEquals(
            1,
            $this->getConnection()->executeQuery('SELECT COUNT(*) FROM SegmentEffort')->fetchOne()
        );

        $this->segmentEffortRepository->delete($segmentEffortOne);
        $this->assertEquals(
            0,
            $this->getConnection()->executeQuery('SELECT COUNT(*) FROM SegmentEffort')->fetchOne()
        );
    }

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->segmentEffortRepository = new DbalSegmentEffortRepository(
            $this->getConnection()
        );
    }
}
