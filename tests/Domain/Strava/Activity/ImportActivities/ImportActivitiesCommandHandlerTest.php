<?php

namespace App\Tests\Domain\Strava\Activity\ImportActivities;

use App\Domain\Strava\Activity\ActivitiesToSkipDuringImport;
use App\Domain\Strava\Activity\ActivityId;
use App\Domain\Strava\Activity\ActivityRepository;
use App\Domain\Strava\Activity\ImportActivities\ImportActivities;
use App\Domain\Strava\Activity\ImportActivities\ImportActivitiesCommandHandler;
use App\Domain\Strava\Activity\NumberOfNewActivitiesToProcessPerImport;
use App\Domain\Strava\Activity\SportType\SportTypesToImport;
use App\Domain\Strava\Activity\Stream\ActivityStreamRepository;
use App\Domain\Strava\Segment\SegmentEffort\SegmentEffortId;
use App\Domain\Strava\Segment\SegmentEffort\SegmentEffortRepository;
use App\Domain\Strava\Segment\SegmentId;
use App\Domain\Strava\Segment\SegmentRepository;
use App\Domain\Strava\Strava;
use App\Domain\Strava\StravaDataImportStatus;
use App\Domain\Weather\OpenMeteo\OpenMeteo;
use App\Infrastructure\Geocoding\Nominatim\Nominatim;
use App\Infrastructure\Repository\Pagination;
use App\Infrastructure\ValueObject\Identifier\UuidFactory;
use App\Tests\ContainerTestCase;
use App\Tests\Domain\Strava\Activity\ActivityBuilder;
use App\Tests\Domain\Strava\Activity\Stream\ActivityStreamBuilder;
use App\Tests\Domain\Strava\Segment\SegmentBuilder;
use App\Tests\Domain\Strava\Segment\SegmentEffort\SegmentEffortBuilder;
use App\Tests\Domain\Strava\SpyStrava;
use App\Tests\SpyOutput;
use League\Flysystem\FilesystemOperator;
use Spatie\Snapshots\MatchesSnapshots;

class ImportActivitiesCommandHandlerTest extends ContainerTestCase
{
    use MatchesSnapshots;

    private ImportActivitiesCommandHandler $importActivitiesCommandHandler;
    private SpyStrava $strava;

    public function testHandleWithTooManyRequests(): void
    {
        $output = new SpyOutput();
        $this->strava->setMaxNumberOfCallsBeforeTriggering429(7);

        $this->getContainer()->get(ActivityRepository::class)->add(
            ActivityBuilder::fromDefaults()
                ->withActivityId(ActivityId::fromUnprefixed(4))
                ->withData([
                    'start_latlng' => [51.2, 3.18],
                ])
                ->build()
        );

        $this->importActivitiesCommandHandler->handle(new ImportActivities($output));

        $this->assertMatchesTextSnapshot((string) $output);

        /** @var \App\Tests\Infrastructure\FileSystem\SpyFileSystem $fileSystem */
        $fileSystem = $this->getContainer()->get(FilesystemOperator::class);
        $this->assertMatchesJsonSnapshot($fileSystem->getWrites());

        $this->assertEmpty(
            $this->getConnection()->executeQuery('SELECT * FROM KeyValue')->fetchAllAssociative()
        );
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
        $this->getContainer()->get(SegmentEffortRepository::class)->add(
            SegmentEffortBuilder::fromDefaults()
                ->withSegmentId(SegmentId::fromUnprefixed(1000))
                ->withSegmentEffortId(SegmentEffortId::random())
                ->withActivityId(ActivityId::fromUnprefixed(1001))
                ->build()
        );
        $this->getContainer()->get(SegmentRepository::class)->add(
            SegmentBuilder::fromDefaults()
                ->withSegmentId(SegmentId::fromUnprefixed(1000))
                ->build()
        );
        $this->getContainer()->get(ActivityStreamRepository::class)->add(
            ActivityStreamBuilder::fromDefaults()
                ->withActivityId(ActivityId::fromUnprefixed(1001))
                ->build()
        );

        $this->importActivitiesCommandHandler->handle(new ImportActivities($output));

        $this->assertMatchesTextSnapshot($output);

        $this->assertMatchesJsonSnapshot(
            $this->getConnection()->executeQuery('SELECT * FROM KeyValue')->fetchAllAssociative()
        );

        $this->assertCount(
            5,
            $this->getContainer()->get(ActivityRepository::class)->findAll()->toArray()
        );
        $this->assertCount(
            0,
            $this->getContainer()->get(SegmentEffortRepository::class)->findByActivityId(ActivityId::fromUnprefixed(1001))
        );
        $this->assertCount(
            0,
            $this->getContainer()->get(SegmentRepository::class)->findAll(Pagination::fromOffsetAndLimit(0, 100))
        );
        $this->assertCount(
            0,
            $this->getContainer()->get(ActivityStreamRepository::class)->findByActivityId(ActivityId::fromUnprefixed(1001))
        );
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

        $this->importActivitiesCommandHandler->handle(new ImportActivities($output));

        $this->assertMatchesTextSnapshot($output);
    }

    public function testHandleWithTooManyActivitiesToProcessInOneImport(): void
    {
        $this->importActivitiesCommandHandler = new ImportActivitiesCommandHandler(
            $this->strava = $this->getContainer()->get(Strava::class),
            $this->getContainer()->get(OpenMeteo::class),
            $this->getContainer()->get(Nominatim::class),
            $this->getContainer()->get(ActivityRepository::class),
            $this->getContainer()->get(FilesystemOperator::class),
            $this->getContainer()->get(SportTypesToImport::class),
            $this->getContainer()->get(ActivitiesToSkipDuringImport::class),
            $this->getContainer()->get(StravaDataImportStatus::class),
            NumberOfNewActivitiesToProcessPerImport::fromInt(1),
            $this->getContainer()->get(UuidFactory::class),
        );

        $output = new SpyOutput();
        $this->strava->setMaxNumberOfCallsBeforeTriggering429(1000);

        $this->getContainer()->get(ActivityRepository::class)->add(
            ActivityBuilder::fromDefaults()
                ->withActivityId(ActivityId::fromUnprefixed(2))
                ->build()
        );

        $this->importActivitiesCommandHandler->handle(new ImportActivities($output));

        $this->assertMatchesTextSnapshot($output);

        $this->assertMatchesJsonSnapshot(
            $this->getConnection()->executeQuery('SELECT * FROM KeyValue')->fetchAllAssociative()
        );

        $this->assertCount(
            2,
            $this->getContainer()->get(ActivityRepository::class)->findAll()->toArray()
        );
    }

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->importActivitiesCommandHandler = new ImportActivitiesCommandHandler(
            $this->strava = $this->getContainer()->get(Strava::class),
            $this->getContainer()->get(OpenMeteo::class),
            $this->getContainer()->get(Nominatim::class),
            $this->getContainer()->get(ActivityRepository::class),
            $this->getContainer()->get(FilesystemOperator::class),
            $this->getContainer()->get(SportTypesToImport::class),
            $this->getContainer()->get(ActivitiesToSkipDuringImport::class),
            $this->getContainer()->get(StravaDataImportStatus::class),
            $this->getContainer()->get(NumberOfNewActivitiesToProcessPerImport::class),
            $this->getContainer()->get(UuidFactory::class),
        );
    }
}
