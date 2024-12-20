<?php

namespace App\Tests\Domain\Strava\Segment;

use App\Domain\Strava\Segment\DbalSegmentRepository;
use App\Domain\Strava\Segment\SegmentEffort\SegmentEffortId;
use App\Domain\Strava\Segment\SegmentEffort\SegmentEffortRepository;
use App\Domain\Strava\Segment\SegmentId;
use App\Domain\Strava\Segment\SegmentRepository;
use App\Domain\Strava\Segment\Segments;
use App\Infrastructure\Exception\EntityNotFound;
use App\Infrastructure\ValueObject\String\Name;
use App\Tests\ContainerTestCase;
use App\Tests\Domain\Strava\Segment\SegmentEffort\SegmentEffortBuilder;

class DbalSegmentRepositoryTest extends ContainerTestCase
{
    private SegmentRepository $segmentRepository;

    public function testFindAndSave(): void
    {
        $segment = SegmentBuilder::fromDefaults()
            ->build();
        $this->segmentRepository->add($segment);

        $this->assertEquals(
            $segment,
            $this->segmentRepository->find($segment->getId())
        );
    }

    public function testItShouldThrowWhenNotFound(): void
    {
        $this->expectException(EntityNotFound::class);
        $this->segmentRepository->find(SegmentId::fromUnprefixed('1'));
    }

    public function testFindAll(): void
    {
        $segmentOne = SegmentBuilder::fromDefaults()
            ->withId(SegmentId::fromUnprefixed(1))
            ->withName(Name::fromString('A name'))
            ->build();
        $this->segmentRepository->add($segmentOne);
        $this->getContainer()->get(SegmentEffortRepository::class)->add(
            SegmentEffortBuilder::fromDefaults()
            ->withId(SegmentEffortId::fromUnprefixed(1))
            ->withSegmentId($segmentOne->getId())
            ->build()
        );
        $this->getContainer()->get(SegmentEffortRepository::class)->add(
            SegmentEffortBuilder::fromDefaults()
                ->withId(SegmentEffortId::fromUnprefixed(2))
                ->withSegmentId($segmentOne->getId())
                ->build()
        );
        $segmentTwo = SegmentBuilder::fromDefaults()
            ->withId(SegmentId::fromUnprefixed(2))
            ->withName(Name::fromString('C name'))
            ->build();
        $this->segmentRepository->add($segmentTwo);
        $segmentThree = SegmentBuilder::fromDefaults()
            ->withId(SegmentId::fromUnprefixed(3))
            ->withName(Name::fromString('B name'))
            ->build();
        $this->segmentRepository->add($segmentThree);
        $this->getContainer()->get(SegmentEffortRepository::class)->add(
            SegmentEffortBuilder::fromDefaults()
                ->withId(SegmentEffortId::fromUnprefixed(3))
                ->withSegmentId($segmentThree->getId())
                ->build()
        );

        $this->assertEquals(
            Segments::fromArray([$segmentOne, $segmentThree, $segmentTwo]),
            $this->segmentRepository->findAll()
        );
    }

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->segmentRepository = new DbalSegmentRepository(
            $this->getConnection()
        );
    }
}
