<?php

namespace App\Domain\Strava\Activity\Image;

interface ImageRepository
{
    /**
     * @return Image[]
     */
    public function findAll(): array;

    public function count(): int;
}
