<?php

declare(strict_types=1);

namespace App\Domain\Strava\Activity\Stream\CalculateBestStreamAverages;

use App\Domain\Strava\Activity\Stream\ActivityPowerRepository;
use App\Domain\Strava\Activity\Stream\ActivityStreamRepository;
use App\Infrastructure\CQRS\Bus\Command;
use App\Infrastructure\CQRS\Bus\CommandHandler;

final readonly class CalculateBestStreamAveragesCommandHandler implements CommandHandler
{
    public function __construct(
        private ActivityStreamRepository $activityStreamRepository,
    ) {
    }

    public function handle(Command $command): void
    {
        assert($command instanceof CalculateBestStreamAverages);
        $command->getOutput()->writeln('Calculating best stream averages...');

        if ($command->getAll()) {
            $command->getOutput()->writeln('  => Including all streams');
        }

        $countCalculatedStreams = 0;
        do {
            $streams = $command->getAll()
                ? $this->activityStreamRepository->findAll()
                : $this->activityStreamRepository->findWithoutBestAverages(100);

            /** @var \App\Domain\Strava\Activity\Stream\ActivityStream $stream */
            foreach ($streams as $stream) {
                $bestAverages = [];
                foreach (ActivityPowerRepository::TIME_INTERVAL_IN_SECONDS_OVERALL as $timeIntervalInSeconds) {
                    if (!$bestAverage = $stream->calculateBestAverageForTimeInterval($timeIntervalInSeconds)) {
                        continue;
                    }
                    $bestAverages[$timeIntervalInSeconds] = $bestAverage;
                }
                ++$countCalculatedStreams;
                $stream->updateBestAverages($bestAverages);
                $this->activityStreamRepository->update($stream);
            }
        } while (!$streams->isEmpty() && !$command->getAll());

        $command->getOutput()->writeln(sprintf('  => Calculated averages for %d streams', $countCalculatedStreams));
    }
}
