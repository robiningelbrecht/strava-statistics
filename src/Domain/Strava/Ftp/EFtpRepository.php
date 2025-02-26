<?php

namespace App\Domain\Strava\Ftp;

use App\Domain\Strava\Activity\Activities;
use App\Domain\Strava\Activity\ActivityType;
use App\Infrastructure\ValueObject\Time\SerializableDateTime;

interface EFtpRepository
{
    public function getNumberOfMonths(): int;

    public function findAllForActivityType(ActivityType $type): EFtps;

    public function findForActivityType(ActivityType $type, SerializableDateTime $dateTime): ?EFtp;

    /**
     * @return array<string>
     */
    public function findEFtpDates(ActivityType $type): array;

    public function enabled(): bool;

    public function enrichWithActivities(Activities $allActivities): void;
}
