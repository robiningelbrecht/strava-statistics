<?php

namespace App\Domain\Strava\Activity\Image;

use App\Domain\Strava\Activity\ActivityRepository;

final readonly class ActivityBasedImageRepository implements ImageRepository
{
    public function __construct(
        private ActivityRepository $activityRepository,
    ) {
    }

    /**
     * @return Image[]
     */
    public function findAll(): array
    {
        $images = [];
        $activities = $this->activityRepository->findAll();
        /** @var \App\Domain\Strava\Activity\Activity $activity */
        foreach ($activities as $activity) {
            if (0 === $activity->getTotalImageCount()) {
                continue;
            }
            $images = [
                ...$images,
                ...array_map(
                    fn (string $path) => Image::create(
                        imageLocation: $path,
                        activity: $activity
                    ),
                    $activity->getLocalImagePaths()
                ),
            ];
        }

        return $images;
    }

    public function count(): int
    {
        $activities = $this->activityRepository->findAll();
        $totalImageCount = 0;

        foreach ($activities as $activity) {
            $totalImageCount += $activity->getTotalImageCount();
        }

        return $totalImageCount;
    }
}
