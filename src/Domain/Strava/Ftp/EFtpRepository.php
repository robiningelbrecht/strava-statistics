<?php

namespace App\Domain\Strava\Ftp;

use App\Infrastructure\ValueObject\Time\SerializableDateTime;
use App\Domain\Strava\Activity\ActivityType;

interface EFtpRepository
{
    public function findAllForActivityType(ActivityType $type): EFtps;

    public function findForActivityType(SerializableDateTime $dateTime, ActivityType $type): ?EFtp;
}
