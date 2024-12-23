<?php

declare(strict_types=1);

namespace App\Domain\Strava\Segment\ImportSegments;

use App\Domain\Strava\Activity\Activity;
use App\Domain\Strava\Activity\ActivityRepository;
use App\Domain\Strava\Segment\Segment;
use App\Domain\Strava\Segment\SegmentEffort\SegmentEffort;
use App\Domain\Strava\Segment\SegmentEffort\SegmentEffortId;
use App\Domain\Strava\Segment\SegmentEffort\SegmentEffortRepository;
use App\Domain\Strava\Segment\SegmentId;
use App\Domain\Strava\Segment\SegmentRepository;
use App\Infrastructure\CQRS\Bus\Command;
use App\Infrastructure\CQRS\Bus\CommandHandler;
use App\Infrastructure\Exception\EntityNotFound;
use App\Infrastructure\ValueObject\String\Name;
use App\Infrastructure\ValueObject\Time\SerializableDateTime;

final readonly class ImportSegmentsCommandHandler implements CommandHandler
{
    public function __construct(
        private ActivityRepository $activityRepository,
        private SegmentRepository $segmentRepository,
        private SegmentEffortRepository $segmentEffortRepository,
    ) {
    }

    public function handle(Command $command): void
    {
        assert($command instanceof ImportSegments);
        $command->getOutput()->writeln('Importing segments and efforts...');

        $segmentsAddedInCurrentRun = [];

        /** @var Activity $activity */
        foreach ($this->activityRepository->findAll() as $activity) {
            if (!$segmentEfforts = $activity->getSegmentEfforts()) {
                // No segments or we already imported and deleted them from the activity.
                continue;
            }

            foreach ($segmentEfforts as $activitySegmentEffort) {
                $activitySegment = $activitySegmentEffort['segment'];
                $segmentId = SegmentId::fromUnprefixed((string) $activitySegment['id']);

                $segment = Segment::create(
                    segmentId: $segmentId,
                    name: Name::fromString($activitySegment['name']),
                    data: array_merge($activitySegment, ['device_name' => $activity->getDeviceName()]),
                );

                // Do not import segments that have been imported in the current run.
                if (!isset($segmentsAddedInCurrentRun[(string) $segmentId])) {
                    // Check if the segment is imported in a previous run.
                    try {
                        $segment = $this->segmentRepository->find($segment->getId());
                    } catch (EntityNotFound) {
                        $this->segmentRepository->add($segment);
                        $segmentsAddedInCurrentRun[(string) $segmentId] = $segmentId;
                        $command->getOutput()->writeln(sprintf('  => Added segment "%s"', $segment->getName()));
                    }
                }

                $segmentEffortId = SegmentEffortId::fromUnprefixed((string) $activitySegmentEffort['id']);
                // TODO: fetch segment data from strava if it's a KOMs to be able to display leaflet map.
                try {
                    $segmentEffort = $this->segmentEffortRepository->find($segmentEffortId);
                    $this->segmentEffortRepository->update($segmentEffort);
                } catch (EntityNotFound) {
                    $this->segmentEffortRepository->add(SegmentEffort::create(
                        segmentEffortId: $segmentEffortId,
                        segmentId: $segment->getId(),
                        activityId: $activity->getId(),
                        startDateTime: SerializableDateTime::createFromFormat(
                            Activity::DATE_TIME_FORMAT,
                            $activitySegmentEffort['start_date_local']
                        ),
                        data: $activitySegmentEffort
                    ));
                    $command->getOutput()->writeln(sprintf('  => Added segment effort for "%s"', $segment->getName()));
                }
            }

            $this->activityRepository->update($activity);
        }
    }
}
