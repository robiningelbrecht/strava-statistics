<?php

namespace App\Domain\Strava\Activity\Image;

use App\Domain\Strava\Activity\ReadModel\ActivityDetails;

final readonly class Image
{
    private function __construct(
        private string $imageLocation,
        private ActivityDetails $activity,
    ) {
    }

    public static function create(
        string $imageLocation,
        ActivityDetails $activity,
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

    public function getActivity(): ActivityDetails
    {
        return $this->activity;
    }
}
