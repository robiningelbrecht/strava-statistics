<?php

declare(strict_types=1);

namespace App\Domain\Strava\Athlete;

interface AthleteRepository
{
    public function save(Athlete $athlete): void;

    public function find(): Athlete;
}
