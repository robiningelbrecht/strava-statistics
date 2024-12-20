<?php

namespace App\Tests\Domain\Strava\Activity\Stream;

use App\Domain\Strava\Activity\ActivityId;
use App\Domain\Strava\Activity\Stream\ActivityStreamRepository;
use App\Domain\Strava\Activity\Stream\ActivityStreams;
use App\Domain\Strava\Activity\Stream\DbalActivityStreamRepository;
use App\Domain\Strava\Activity\Stream\StreamType;
use App\Domain\Strava\Activity\Stream\StreamTypes;
use App\Infrastructure\Exception\EntityNotFound;
use App\Tests\ContainerTestCase;

class DbalActivityStreamRepositoryTest extends ContainerTestCase
{
    private ActivityStreamRepository $activityStreamRepository;

    public function testIsImportedForActivity(): void
    {
        $stream = ActivityStreamBuilder::fromDefaults()->build();
        $this->activityStreamRepository->add($stream);

        $this->assertTrue($this->activityStreamRepository->isImportedForActivity($stream->getActivityId()));
        $this->assertFalse($this->activityStreamRepository->isImportedForActivity(ActivityId::fromUnprefixed('1')));
    }

    public function testUpdate(): void
    {
        $stream = ActivityStreamBuilder::fromDefaults()->build();
        $this->activityStreamRepository->add($stream);

        $this->assertEmpty($stream->getBestAverages());

        $stream->updateBestAverages([1 => 1]);
        $this->activityStreamRepository->update($stream);

        $streams = $this->activityStreamRepository->findByActivityId($stream->getActivityId());
        /** @var \App\Domain\Strava\Activity\Stream\ActivityStream $stream */
        $stream = $streams->getFirst();

        $this->assertEquals([1 => 1], $stream->getBestAverages());
    }

    public function testHasOneForActivityAndStreamType(): void
    {
        $stream = ActivityStreamBuilder::fromDefaults()->build();
        $this->activityStreamRepository->add($stream);

        $this->assertTrue($this->activityStreamRepository->hasOneForActivityAndStreamType(
            activityId: $stream->getActivityId(),
            streamType: $stream->getStreamType()
        ));
        $this->assertFalse($this->activityStreamRepository->hasOneForActivityAndStreamType(
            activityId: ActivityId::fromUnprefixed(1),
            streamType: $stream->getStreamType()
        ));
        $this->assertFalse($this->activityStreamRepository->hasOneForActivityAndStreamType(
            activityId: $stream->getActivityId(),
            streamType: StreamType::CADENCE
        ));
    }

    public function testFindByStreamType(): void
    {
        $stream = ActivityStreamBuilder::fromDefaults()->build();
        $this->activityStreamRepository->add($stream);

        $this->assertEquals(
            ActivityStreams::fromArray([$stream]),
            $this->activityStreamRepository->findByStreamType($stream->getStreamType())
        );
    }

    public function testFindByActivityAndStreamTypes(): void
    {
        $streamOne = ActivityStreamBuilder::fromDefaults()
            ->withActivityId(ActivityId::fromUnprefixed(1))
            ->withStreamType(StreamType::WATTS)
            ->build();
        $this->activityStreamRepository->add($streamOne);
        $streamTwo = ActivityStreamBuilder::fromDefaults()
            ->withActivityId(ActivityId::fromUnprefixed(1))
            ->withStreamType(StreamType::CADENCE)
            ->build();
        $this->activityStreamRepository->add($streamTwo);
        $this->activityStreamRepository->add(
            ActivityStreamBuilder::fromDefaults()
                ->withActivityId(ActivityId::fromUnprefixed(1))
                ->withStreamType(StreamType::HEART_RATE)
                ->build()
        );
        $this->activityStreamRepository->add(
            ActivityStreamBuilder::fromDefaults()
                ->withActivityId(ActivityId::fromUnprefixed(2))
                ->withStreamType(StreamType::CADENCE)
                ->build()
        );

        $this->assertEquals(
            ActivityStreams::fromArray([$streamTwo, $streamOne]),
            $this->activityStreamRepository->findByActivityAndStreamTypes(
                activityId: ActivityId::fromUnprefixed(1),
                streamTypes: StreamTypes::fromArray([StreamType::WATTS, StreamType::CADENCE])
            )
        );
    }

    public function testFindByActivity(): void
    {
        $streamOne = ActivityStreamBuilder::fromDefaults()
            ->withActivityId(ActivityId::fromUnprefixed(1))
            ->withStreamType(StreamType::WATTS)
            ->build();
        $this->activityStreamRepository->add($streamOne);
        $streamTwo = ActivityStreamBuilder::fromDefaults()
            ->withActivityId(ActivityId::fromUnprefixed(1))
            ->withStreamType(StreamType::CADENCE)
            ->build();
        $this->activityStreamRepository->add($streamTwo);
        $this->activityStreamRepository->add(
            ActivityStreamBuilder::fromDefaults()
                ->withActivityId(ActivityId::fromUnprefixed(2))
                ->withStreamType(StreamType::CADENCE)
                ->build()
        );

        $this->assertEquals(
            ActivityStreams::fromArray([$streamTwo, $streamOne]),
            $this->activityStreamRepository->findByActivityId(
                activityId: ActivityId::fromUnprefixed(1),
            )
        );
    }

    public function testDelete(): void
    {
        $streamOne = ActivityStreamBuilder::fromDefaults()
            ->withActivityId(ActivityId::fromUnprefixed(1))
            ->withStreamType(StreamType::WATTS)
            ->build();
        $this->activityStreamRepository->add($streamOne);
        $streamTwo = ActivityStreamBuilder::fromDefaults()
            ->withActivityId(ActivityId::fromUnprefixed(1))
            ->withStreamType(StreamType::CADENCE)
            ->build();
        $this->activityStreamRepository->add($streamTwo);

        $this->assertEquals(
            2,
            $this->getConnection()
                ->executeQuery('SELECT COUNT(*) FROM ActivityStream')->fetchOne()
        );

        $this->activityStreamRepository->delete($streamOne);
        $this->assertEquals(
            1,
            $this->getConnection()
                ->executeQuery('SELECT COUNT(*) FROM ActivityStream')->fetchOne()
        );
    }

    public function testFindWithoutBestAverages(): void
    {
        $streamOne = ActivityStreamBuilder::fromDefaults()
            ->withActivityId(ActivityId::fromUnprefixed(1))
            ->withStreamType(StreamType::WATTS)
            ->build();
        $this->activityStreamRepository->add($streamOne);
        $streamTwo = ActivityStreamBuilder::fromDefaults()
            ->withActivityId(ActivityId::fromUnprefixed(1))
            ->withStreamType(StreamType::CADENCE)
            ->withBestAverages(['lol'])
            ->build();
        $this->activityStreamRepository->add($streamTwo);

        $this->assertEquals(
            ActivityStreams::fromArray([$streamOne]),
            $this->activityStreamRepository->findWithoutBestAverages()
        );
    }

    public function testFindWithBestAverageFor(): void
    {
        $streamOne = ActivityStreamBuilder::fromDefaults()
            ->withActivityId(ActivityId::fromUnprefixed(1))
            ->withStreamType(StreamType::WATTS)
            ->withBestAverages(['10' => 40])
            ->build();
        $this->activityStreamRepository->add($streamOne);
        $streamTwo = ActivityStreamBuilder::fromDefaults()
            ->withActivityId(ActivityId::fromUnprefixed(2))
            ->withStreamType(StreamType::WATTS)
            ->withBestAverages(['10' => 30])
            ->build();
        $this->activityStreamRepository->add($streamTwo);

        $this->assertEquals(
            $streamOne,
            $this->activityStreamRepository->findWithBestAverageFor(10, StreamType::WATTS)
        );
    }

    public function testFindWithBestAverageForItShouldThrowWhenNotFound(): void
    {
        $streamOne = ActivityStreamBuilder::fromDefaults()
            ->withActivityId(ActivityId::fromUnprefixed(1))
            ->withStreamType(StreamType::WATTS)
            ->withBestAverages(['10' => 40])
            ->build();
        $this->activityStreamRepository->add($streamOne);
        $streamTwo = ActivityStreamBuilder::fromDefaults()
            ->withActivityId(ActivityId::fromUnprefixed(2))
            ->withStreamType(StreamType::WATTS)
            ->withBestAverages(['10' => 30])
            ->build();
        $this->activityStreamRepository->add($streamTwo);

        $this->expectExceptionObject(new EntityNotFound('ActivityStream for average not found'));

        $this->activityStreamRepository->findWithBestAverageFor(20, StreamType::WATTS);
    }

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->activityStreamRepository = new DbalActivityStreamRepository(
            $this->getConnection(),
        );
    }
}
