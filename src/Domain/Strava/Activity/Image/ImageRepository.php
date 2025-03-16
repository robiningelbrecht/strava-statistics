<?php

namespace App\Domain\Strava\Activity\Image;

interface ImageRepository
{
    public function findAll(): Images;

    public function count(): int;
}
