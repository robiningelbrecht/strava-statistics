<?php

namespace App\Domain\Strava\Activity\Stream;

use App\Domain\Strava\Activity\ActivityId;
use App\Domain\Strava\Activity\ActivityRepository;
use App\Domain\Strava\Athlete\Weight\AthleteWeightRepository;
use App\Infrastructure\Exception\EntityNotFound;
use Carbon\CarbonInterval;

final class StreamBasedActivityPowerRepository implements ActivityPowerRepository
{
    /** @var array<mixed> */
    private static array $cachedPowerOutputs = [];

    public function __construct(
        private readonly ActivityRepository $activityRepository,
        private readonly AthleteWeightRepository $athleteWeightRepository,
        private readonly ActivityStreamRepository $activityStreamRepository,
    ) {
    }

    /**
     * @return array<mixed>
     */
    public function findBestForActivity(ActivityId $activityId): array
    {
        if (array_key_exists((string) $activityId, StreamBasedActivityPowerRepository::$cachedPowerOutputs)) {
            return StreamBasedActivityPowerRepository::$cachedPowerOutputs[(string) $activityId];
        }

        $activities = $this->activityRepository->findAll();
        $powerStreams = $this->activityStreamRepository->findByStreamType(StreamType::WATTS);

        /** @var \App\Domain\Strava\Activity\Activity $activity */
        foreach ($activities as $activity) {
            StreamBasedActivityPowerRepository::$cachedPowerOutputs[(string) $activity->getId()] = [];
            $powerStreamsForActivity = $powerStreams->filter(fn (ActivityStream $stream) => $stream->getActivityId() == $activity->getId());

            if ($powerStreamsForActivity->isEmpty()) {
                continue;
            }

            /** @var ActivityStream $activityStream */
            $activityStream = $powerStreamsForActivity->getFirst();
            $bestAverages = $activityStream->getBestAverages();

            foreach (self::TIME_INTERVAL_IN_SECONDS as $timeIntervalInSeconds) {
                $interval = CarbonInterval::seconds($timeIntervalInSeconds);
                if (!isset($bestAverages[$timeIntervalInSeconds])) {
                    continue;
                }
                $bestAverageForTimeInterval = $bestAverages[$timeIntervalInSeconds];

                $athleteWeight = $this->athleteWeightRepository->find($activity->getStartDate())->getWeightInKg();
                $relativePower = $athleteWeight > 0 ? round($bestAverageForTimeInterval / $athleteWeight, 2) : 0;
                StreamBasedActivityPowerRepository::$cachedPowerOutputs[(string) $activity->getId()][$timeIntervalInSeconds] = PowerOutput::fromState(
                    time: (int) $interval->totalHours ? $interval->totalHours.' h' : ((int) $interval->totalMinutes ? $interval->totalMinutes.' m' : $interval->totalSeconds.' s'),
                    power: $bestAverageForTimeInterval,
                    relativePower: $relativePower,
                );
            }
        }

        return StreamBasedActivityPowerRepository::$cachedPowerOutputs[(string) $activityId];
    }

    /**
     * @return array<int, int>
     */
    public function findTimeInSecondsPerWattageForActivity(ActivityId $activityId): array
    {
        if (!$this->activityStreamRepository->hasOneForActivityAndStreamType(
            activityId: $activityId,
            streamType: StreamType::WATTS
        )) {
            return [];
        }

        $streams = $this->activityStreamRepository->findByActivityAndStreamTypes(
            activityId: $activityId,
            streamTypes: StreamTypes::fromArray([StreamType::WATTS])
        );
        /** @var ActivityStream $stream */
        $stream = $streams->getByStreamType(StreamType::WATTS);
        $powerStreamForActivity = array_count_values(array_filter($stream->getData(), fn (mixed $item) => !is_null($item)));
        ksort($powerStreamForActivity);

        return $powerStreamForActivity;
    }

    /**
     * @return PowerOutput[]
     */
    public function findBest(): array
    {
        /** @var PowerOutput[] $best */
        $best = [];

        foreach (self::TIME_INTERVAL_IN_SECONDS_OVERALL as $timeIntervalInSeconds) {
            try {
                $stream = $this->activityStreamRepository->findWithBestAverageFor(
                    intervalInSeconds: $timeIntervalInSeconds,
                    streamType: StreamType::WATTS
                );
            } catch (EntityNotFound) {
                continue;
            }

            $activity = $this->activityRepository->find($stream->getActivityId());
            $interval = CarbonInterval::seconds($timeIntervalInSeconds);
            $bestAverageForTimeInterval = $stream->getBestAverages()[$timeIntervalInSeconds];

            $athleteWeight = $this->athleteWeightRepository->find($activity->getStartDate())->getWeightInKg();
            $relativePower = $athleteWeight > 0 ? round($bestAverageForTimeInterval / $athleteWeight, 2) : 0;
            $best[$timeIntervalInSeconds] = PowerOutput::fromState(
                time: (int) $interval->totalHours ? $interval->totalHours.' h' : ((int) $interval->totalMinutes ? $interval->totalMinutes.' m' : $interval->totalSeconds.' s'),
                power: $bestAverageForTimeInterval,
                relativePower: $relativePower,
                activity: $activity,
            );
        }

        return $best;
    }
}
