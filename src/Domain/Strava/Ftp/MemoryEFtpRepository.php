<?php

namespace App\Domain\Strava\Ftp;

use App\Infrastructure\Exception\EntityNotFound;
use App\Infrastructure\ValueObject\Time\SerializableDateTime;
use App\Domain\Strava\Activity\Activities;
use App\Domain\Strava\Activity\Activity;
use App\Domain\Strava\Activity\ActivityType;
use Carbon\Carbon;

final readonly class MemoryEFtpRepository implements EFtpRepository
{
    private function __construct(
        private Eftps $Eftps
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

    public static function fromActivities(Activities $activities): MemoryEFtpRepository
    {
        $eftps = [];
        $activitiesWithEFTP = $activities->filter(fn (Activity $activity) => $activity->getEFTP() !== null);

        foreach ($activitiesWithEFTP as $activity) {
            $eftps[] = Eftp::fromState(
                $activity->getStartDate(), 
                $activity->getEFTP(),
                $activity->getSportType()->getActivityType()
            );
        }

        return new self(Eftps::fromArray($eftps));
    }
}
