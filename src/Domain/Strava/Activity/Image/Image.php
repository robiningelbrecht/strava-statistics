<?php

namespace App\Domain\Strava\Activity\Image;

use App\Domain\Strava\Activity\WriteModel\Activity;

final readonly class Image
{
    private function __construct(
        private string $imageLocation,
        private Activity $activity,
    ) {
    }

    public static function create(
        string $imageLocation,
        Activity $activity,
    ): self {
        return new self(
            imageLocation: $imageLocation,
            activity: $activity
        );
    }

    public function getImageUrl(): string
    {
        return $this->imageLocation;
    }

    public function getActivity(): Activity
    {
        return $this->activity;
    }
}
