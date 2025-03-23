<?php

declare(strict_types=1);

namespace App\Domain\Strava\Activity\BestEffort\CalculateBestActivityEfforts;

use App\Domain\Strava\Activity\ActivityRepository;
use App\Domain\Strava\Activity\BestEffort\ActivityBestEffort;
use App\Domain\Strava\Activity\BestEffort\ActivityBestEffortRepository;
use App\Domain\Strava\Activity\Stream\ActivityStreamRepository;
use App\Domain\Strava\Activity\Stream\StreamType;
use App\Infrastructure\CQRS\Command;
use App\Infrastructure\CQRS\CommandHandler;
use App\Infrastructure\Exception\EntityNotFound;

final readonly class CalculateBestActivityEffortsCommandHandler implements CommandHandler
{
    public function __construct(
        private ActivityRepository $activityRepository,
        private ActivityBestEffortRepository $activityBestEffortRepository,
        private ActivityStreamRepository $activityStreamRepository,
    ) {
    }

    public function handle(Command $command): void
    {
        assert($command instanceof CalculateBestActivityEfforts);
        $command->getOutput()->writeln('Calculating best activity efforts...');

        $activityIdsWithoutBestEfforts = $this->activityBestEffortRepository->findActivityIdsWithoutBestEfforts();

        $activityWithBestEffortsCalculatedCount = 0;
        foreach ($activityIdsWithoutBestEfforts as $activityId) {
            try {
                $distanceStream = $this->activityStreamRepository->findOneByActivityAndStreamType($activityId, StreamType::DISTANCE);
                $timeStream = $this->activityStreamRepository->findOneByActivityAndStreamType($activityId, StreamType::TIME);
            } catch (EntityNotFound) {
                continue;
            }
            ++$activityWithBestEffortsCalculatedCount;

            $activity = $this->activityRepository->find($activityId);
            $distances = $distanceStream->getData();
            $time = $timeStream->getData();

            if (!$distancesForBestEfforts = $activity->getSportType()->getDistancesForBestEffortCalculation()) {
                continue;
            }

            foreach ($distancesForBestEfforts as $distance) {
                $n = count($distances);
                $fastestTime = PHP_INT_MAX;
                $startIdx = 0;

                for ($endIdx = 0; $endIdx < $n; ++$endIdx) {
                    while ($startIdx < $endIdx && ($distances[$endIdx] - $distances[$startIdx]) >= $distance->toInt()) {
                        $fastestTime = min($fastestTime, $time[$endIdx] - $time[$startIdx]);
                        ++$startIdx;
                    }
                }

                if (PHP_INT_MAX === $fastestTime) {
                    // No fastest time for this distance.
                    continue;
                }

                $this->activityBestEffortRepository->add(
                    ActivityBestEffort::create(
                        activityId: $activityId,
                        distanceInMeter: $distance,
                        sportType: $activity->getSportType(),
                        timeInSeconds: $fastestTime,
                    )
                );
            }
        }
        $command->getOutput()->writeln(sprintf('  => Calculated best efforts for %d activities', $activityWithBestEffortsCalculatedCount));
    }
}
