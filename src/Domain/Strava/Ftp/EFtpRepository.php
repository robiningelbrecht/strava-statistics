<?php

namespace App\Domain\Strava\Ftp;

use App\Domain\Strava\Activity\ActivityType;
use App\Infrastructure\ValueObject\Time\SerializableDateTime;

interface EFtpRepository
{
    public function findAllForActivityType(ActivityType $type): EFtps;

    public function findForActivityType(SerializableDateTime $dateTime, ActivityType $type): ?EFtp;
}
