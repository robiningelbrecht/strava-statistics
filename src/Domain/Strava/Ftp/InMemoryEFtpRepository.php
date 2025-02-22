<?php

namespace App\Domain\Strava\Ftp;

use App\Domain\Strava\Activity\Activities;
use App\Domain\Strava\Activity\ActivityType;
use App\Infrastructure\ValueObject\Time\SerializableDateTime;

final readonly class InMemoryEFtpRepository implements EFtpRepository
{
    private function __construct(
        private EFtps $Eftps,
    ) {
    }

    public function findAllForActivityType(ActivityType $type): EFtps
    {
        return $this->Eftps->filter(fn (EFtp $eftp) => $eftp->getActivityType() === $type);
    }

    public function findForActivityType(SerializableDateTime $dateTime, ActivityType $type): ?EFtp
    {
        return $this->findAllForActivityType($type)->findForDate($dateTime);
    }

    public static function fromActivities(Activities $allActivities): EFtpRepository
    {
        $eftps = [];

        foreach ($allActivities as $activity) {
            if (null === $activity->getEFTP()) {
                continue;
            }

            $eftps[] = EFtp::fromState(
                $activity->getStartDate(),
                $activity->getEFTP(),
                $activity->getSportType()->getActivityType()
            );
        }

        return new self(EFtps::fromArray($eftps));
    }
}
