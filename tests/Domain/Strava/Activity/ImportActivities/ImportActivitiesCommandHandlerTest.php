<?php

namespace App\Tests\Domain\Strava\Activity\ImportActivities;

use App\Domain\Strava\Activity\ActivityId;
use App\Domain\Strava\Activity\ActivityRepository;
use App\Domain\Strava\Activity\ImportActivities\ImportActivities;
use App\Domain\Strava\Activity\Stream\ActivityStreamRepository;
use App\Domain\Strava\Segment\SegmentEffort\SegmentEffortRepository;
use App\Domain\Strava\Strava;
use App\Infrastructure\CQRS\Bus\CommandBus;
use App\Tests\ContainerTestCase;
use App\Tests\Domain\Strava\Activity\ActivityBuilder;
use App\Tests\Domain\Strava\Activity\Stream\ActivityStreamBuilder;
use App\Tests\Domain\Strava\Segment\SegmentEffort\SegmentEffortBuilder;
use App\Tests\Domain\Strava\SpyStrava;
use App\Tests\SpyOutput;
use League\Flysystem\FilesystemOperator;
use Spatie\Snapshots\MatchesSnapshots;

class ImportActivitiesCommandHandlerTest extends ContainerTestCase
{
    use MatchesSnapshots;

    private CommandBus $commandBus;
    private SpyStrava $strava;

    public function testHandleWithTooManyRequests(): void
    {
        $output = new SpyOutput();
        $this->strava->setMaxNumberOfCallsBeforeTriggering429(8);

        $this->getContainer()->get(ActivityRepository::class)->add(
            ActivityBuilder::fromDefaults()
                ->withActivityId(ActivityId::fromUnprefixed(4))
                ->withData([
                    'start_latlng' => [51.2, 3.18],
                ])
                ->build()
        );

        $this->commandBus->dispatch(new ImportActivities($output));

        $this->assertMatchesTextSnapshot((string) $output);

        /** @var \App\Tests\Infrastructure\FileSystem\SpyFileSystem $fileSystem */
        $fileSystem = $this->getContainer()->get(FilesystemOperator::class);
        $this->assertMatchesJsonSnapshot($fileSystem->getWrites());
    }

    public function testHandleWithActivityDelete(): void
    {
        $output = new SpyOutput();
        $this->strava->setMaxNumberOfCallsBeforeTriggering429(1000);

        $this->getContainer()->get(ActivityRepository::class)->add(
            ActivityBuilder::fromDefaults()
                ->withActivityId(ActivityId::fromUnprefixed(4))
                ->build()
        );

        $this->getContainer()->get(ActivityRepository::class)->add(
            ActivityBuilder::fromDefaults()
                ->withData([
                    'kudos_count' => 1,
                    'name' => 'Delete this one',
                ])
                ->withActivityId(ActivityId::fromUnprefixed(1000))
                ->build()
        );
        $segmentEffortOne = SegmentEffortBuilder::fromDefaults()
            ->withActivityId(ActivityId::fromUnprefixed(1000))
            ->build();
        $this->getContainer()->get(SegmentEffortRepository::class)->add($segmentEffortOne);
        $stream = ActivityStreamBuilder::fromDefaults()
            ->withActivityId(ActivityId::fromUnprefixed(1000))
            ->build();
        $this->getContainer()->get(ActivityStreamRepository::class)->add($stream);

        $this->getContainer()->get(ActivityRepository::class)->add(
            ActivityBuilder::fromDefaults()
                ->withData([
                    'kudos_count' => 1,
                    'name' => 'Delete this one as well',
                ])
                ->withActivityId(ActivityId::fromUnprefixed(1001))
                ->build()
        );

        $this->commandBus->dispatch(new ImportActivities($output));

        $this->assertMatchesTextSnapshot($output);
    }

    public function testHandleWithoutActivityDelete(): void
    {
        $output = new SpyOutput();
        $this->strava->setMaxNumberOfCallsBeforeTriggering429(1000);

        $this->getContainer()->get(ActivityRepository::class)->add(
            ActivityBuilder::fromDefaults()
                ->withActivityId(ActivityId::fromUnprefixed(4))
                ->build()
        );

        $this->commandBus->dispatch(new ImportActivities($output));

        $this->assertMatchesTextSnapshot($output);
    }

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->commandBus = $this->getContainer()->get(CommandBus::class);
        $this->strava = $this->getContainer()->get(Strava::class);
    }
}
