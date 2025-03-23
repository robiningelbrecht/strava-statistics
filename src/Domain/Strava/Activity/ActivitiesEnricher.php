<?php

declare(strict_types=1);

namespace App\Domain\Strava\Activity;

use App\Domain\Strava\Activity\SportType\SportTypeRepository;
use App\Domain\Strava\Activity\Stream\ActivityPowerRepository;
use App\Domain\Strava\Activity\Stream\ActivityStreamRepository;
use App\Domain\Strava\Activity\Stream\StreamType;
use App\Infrastructure\Exception\EntityNotFound;

final class ActivitiesEnricher
{
    private Activities $enrichedActivities;
    /** @var array<string, Activities> */
    private array $activitiesPerActivityType;
    /** @var array<string, Activities> */
    private array $activitiesPerSportType;

    public function __construct(
        private readonly ActivityRepository $activityRepository,
        private readonly ActivityPowerRepository $activityPowerRepository,
        private readonly ActivityStreamRepository $activityStreamRepository,
        private readonly ActivityTypeRepository $activityTypeRepository,
        private readonly SportTypeRepository $sportTypeRepository,
    ) {
        $this->enrichedActivities = Activities::empty();
        $this->activitiesPerActivityType = [];
        $this->activitiesPerSportType = [];
    }

    private function enrichAll(): Activities
    {
        $activities = $this->activityRepository->findAll();

        foreach ($activities as $activity) {
            $activity->enrichWithBestPowerOutputs(
                $this->activityPowerRepository->findBestForActivity($activity->getId())
            );

            try {
                $cadenceStream = $this->activityStreamRepository->findOneByActivityAndStreamType(
                    activityId: $activity->getId(),
                    streamType: StreamType::CADENCE
                );

                if (!empty($cadenceStream->getData())) {
                    $activity->enrichWithMaxCadence(max($cadenceStream->getData()));
                }
            } catch (EntityNotFound) {
            }
        }

        $this->enrichedActivities = $activities;

        return $activities;
    }

    public function getEnrichedActivities(): Activities
    {
        if ($this->enrichedActivities->isEmpty()) {
            $this->enrichedActivities = $this->enrichAll();
        }

        return $this->enrichedActivities;
    }

    /**
     * @return array<string, Activities>
     */
    public function getActivitiesPerActivityType(): array
    {
        if (empty($this->activitiesPerActivityType)) {
            $activityTypes = $this->activityTypeRepository->findAll();

            foreach ($activityTypes as $activityType) {
                $this->activitiesPerActivityType[$activityType->value] = $this->getEnrichedActivities()->filterOnActivityType($activityType);
            }
        }

        return $this->activitiesPerActivityType;
    }

    /**
     * @return array<string, Activities>
     */
    public function getActivitiesPerSportType(): array
    {
        if (empty($this->activitiesPerSportType)) {
            $sportTypes = $this->sportTypeRepository->findAll();

            foreach ($sportTypes as $sportType) {
                $this->activitiesPerSportType[$sportType->value] = $this->getEnrichedActivities()->filterOnSportType($sportType);
            }
        }

        return $this->activitiesPerSportType;
    }
}
