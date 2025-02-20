<?php

namespace App\Domain\Strava\Activity\Stream;

use App\Domain\Strava\Activity\ActivityId;
use App\Domain\Strava\Activity\ActivityRepository;
use App\Domain\Strava\Activity\ActivityType;
use App\Domain\Strava\Activity\SportType\SportType;
use App\Domain\Strava\Athlete\Weight\AthleteWeightRepository;
use App\Infrastructure\Exception\EntityNotFound;
use App\Infrastructure\Serialization\Json;
use App\Infrastructure\ValueObject\Time\DateRange;
use App\Infrastructure\ValueObject\Time\SerializableDateTime;
use Carbon\CarbonInterval;
use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\Connection;

final class StreamBasedActivityPowerRepository implements ActivityPowerRepository
{
    /** @var array<mixed> */
    private static array $cachedPowerOutputs = [];

    public function __construct(
        private readonly Connection $connection,
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

                try {
                    $athleteWeight = $this->athleteWeightRepository->find($activity->getStartDate())->getWeightInKg();
                } catch (EntityNotFound) {
                    throw new EntityNotFound(sprintf('Trying to calculate the relative power for activity "%s" on %s, but no corresponding athleteWeight was found. 
                    Make sure you configure the proper weights in your .env file. Do not forgot to run the app:strava:import-data command after changing the weights', $activity->getName(), $activity->getStartDate()->format('Y-m-d')));
                }

                $relativePower = $athleteWeight->toFloat() > 0 ? round($bestAverageForTimeInterval / $athleteWeight->toFloat(), 2) : 0;
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

        $stream = $this->activityStreamRepository->findOneByActivityAndStreamType(
            activityId: $activityId,
            streamType: StreamType::WATTS
        );
        $powerStreamForActivity = array_count_values(array_filter($stream->getData(), fn (mixed $item) => !is_null($item)));
        ksort($powerStreamForActivity);

        return $powerStreamForActivity;
    }

    /**
     * @return PowerOutput[]
     */
    public function findBestForActivityType(ActivityType $activityType): array
    {
        /** @var PowerOutput[] $best */
        $best = [];

        foreach (self::TIME_INTERVAL_IN_SECONDS_OVERALL as $timeIntervalInSeconds) {
            $query = 'SELECT ActivityStream.* FROM ActivityStream 
                        INNER JOIN Activity ON Activity.activityId = ActivityStream.activityId 
                        WHERE streamType = :streamType
                        AND Activity.sportType IN(:sportType)
                        AND JSON_EXTRACT(bestAverages, "$.'.$timeIntervalInSeconds.'") IS NOT NULL
                        ORDER BY JSON_EXTRACT(bestAverages, "$.'.$timeIntervalInSeconds.'") DESC, createdOn DESC LIMIT 1';

            if (!$result = $this->connection->executeQuery(
                $query,
                [
                    'streamType' => StreamType::WATTS->value,
                    'sportType' => $activityType->getSportTypes()->map(fn (SportType $sportType) => $sportType->value),
                ],
                [
                    'sportType' => ArrayParameterType::STRING,
                ]
            )->fetchAssociative()) {
                continue;
            }

            $stream = ActivityStream::fromState(
                activityId: ActivityId::fromString($result['activityId']),
                streamType: StreamType::from($result['streamType']),
                streamData: Json::decode($result['data']),
                createdOn: SerializableDateTime::fromString($result['createdOn']),
                bestAverages: Json::decode($result['bestAverages'] ?? '[]'),
            );

            $activity = $this->activityRepository->find($stream->getActivityId());
            $interval = CarbonInterval::seconds($timeIntervalInSeconds);
            $bestAverageForTimeInterval = $stream->getBestAverages()[$timeIntervalInSeconds];

            try {
                $athleteWeight = $this->athleteWeightRepository->find($activity->getStartDate())->getWeightInKg();
            } catch (EntityNotFound) {
                throw new EntityNotFound(sprintf('Trying to calculate the relative power for activity "%s" on %s, but no corresponding athleteWeight was found. 
                    Make sure you configure the proper weights in your .env file. Do not forgot to run the app:strava:import-data command after changing the weights', $activity->getName(), $activity->getStartDate()->format('Y-m-d')));
            }

            $relativePower = $athleteWeight->toFloat() > 0 ? round($bestAverageForTimeInterval / $athleteWeight->toFloat(), 2) : 0;
            $best[$timeIntervalInSeconds] = PowerOutput::fromState(
                time: (int) $interval->totalHours ? $interval->totalHours.' h' : ((int) $interval->totalMinutes ? $interval->totalMinutes.' m' : $interval->totalSeconds.' s'),
                power: $bestAverageForTimeInterval,
                relativePower: $relativePower,
                activity: $activity,
            );
        }

        return $best;
    }

    /**
     * @return PowerOutput[]
     */
    public function findBestForActivityTypeInDateRange(ActivityType $activityType, DateRange $dateRange): array
    {
    }
}
