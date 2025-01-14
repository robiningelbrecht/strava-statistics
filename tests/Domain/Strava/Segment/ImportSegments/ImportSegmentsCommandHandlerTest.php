<?php

namespace App\Tests\Domain\Strava\Segment\ImportSegments;

use App\Domain\Strava\Activity\ActivityId;
use App\Domain\Strava\Activity\WriteModel\ActivityRepository;
use App\Domain\Strava\Segment\ImportSegments\ImportSegments;
use App\Domain\Strava\Segment\SegmentEffort\SegmentEffortId;
use App\Domain\Strava\Segment\SegmentEffort\SegmentEffortRepository;
use App\Domain\Strava\Segment\SegmentId;
use App\Infrastructure\CQRS\Bus\CommandBus;
use App\Tests\ContainerTestCase;
use App\Tests\Domain\Strava\Activity\WriteModel\ActivityBuilder;
use App\Tests\Domain\Strava\Segment\SegmentEffort\SegmentEffortBuilder;
use App\Tests\SpyOutput;
use Spatie\Snapshots\MatchesSnapshots;

class ImportSegmentsCommandHandlerTest extends ContainerTestCase
{
    use MatchesSnapshots;

    private CommandBus $commandBus;

    public function testHandle(): void
    {
        $output = new SpyOutput();

        $this->getContainer()->get(ActivityRepository::class)->add(
            ActivityBuilder::fromDefaults()
                ->withActivityId(ActivityId::fromUnprefixed(1))
                ->withData([
                    'name' => 'Test activity 1',
                    'device_name' => 'Zwift',
                    'segment_efforts' => [
                        [
                            'id' => '1',
                            'start_date_local' => '2023-07-29T09:34:03Z',
                            'segment' => [
                                'id' => '1',
                                'name' => 'Segment One',
                            ],
                        ],
                    ],
                ])
                ->build()
        );
        $this->getContainer()->get(ActivityRepository::class)->add(
            ActivityBuilder::fromDefaults()
                ->withActivityId(ActivityId::fromUnprefixed(2))
                ->withData([
                    'name' => 'Test activity 2',
                    'segment_efforts' => [
                        [
                            'id' => '2',
                            'start_date_local' => '2023-07-29T09:34:03Z',
                            'segment' => [
                                'id' => '1',
                                'name' => 'Segment One',
                            ],
                        ],
                    ],
                ])
                ->build()
        );
        $this->getContainer()->get(ActivityRepository::class)->add(
            ActivityBuilder::fromDefaults()
                ->withActivityId(ActivityId::fromUnprefixed(3))
                ->withData([
                    'name' => 'Test activity 3',
                ])
                ->build()
        );
        $this->getContainer()->get(SegmentEffortRepository::class)->add(
            SegmentEffortBuilder::fromDefaults()
                ->withSegmentEffortId(SegmentEffortId::fromUnprefixed(2))
                ->withSegmentId(SegmentId::fromUnprefixed('1'))
                ->withActivityId(ActivityId::fromUnprefixed(9542782314))
                ->withData([
                    'elapsed_time' => 9.3,
                    'average_watts' => 200,
                    'distance' => 100,
                ])
                ->build()
        );

        $this->commandBus->dispatch(new ImportSegments($output));
        $this->assertMatchesTextSnapshot($output);

        $this->assertMatchesJsonSnapshot(
            $this->getConnection()->executeQuery('SELECT * FROM Segment')->fetchAllAssociative()
        );
    }

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->commandBus = $this->getContainer()->get(CommandBus::class);
    }
}
