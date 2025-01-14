<?php

namespace App\Domain\Strava\Activity\Image;

use App\Domain\Strava\Activity\ReadModel\ActivityDetailsRepository;

final readonly class ActivityBasedImageRepository implements ImageRepository
{
    public function __construct(
        private ActivityDetailsRepository $activityRepository,
    ) {
    }

    /**
     * @return Image[]
     */
    public function findAll(): array
    {
        $images = [];
        $activities = $this->activityRepository->findAll();
        /** @var \App\Domain\Strava\Activity\ReadModel\ActivityDetails $activity */
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
}
