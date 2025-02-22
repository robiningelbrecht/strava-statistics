<?php

namespace App\Domain\Strava\Ftp;

use App\Infrastructure\Exception\EntityNotFound;
use App\Infrastructure\ValueObject\Time\SerializableDateTime;
use App\Domain\Strava\Activity\Activities;
use App\Domain\Strava\Activity\Activity;
use App\Domain\Strava\Activity\ActivityType;
use Carbon\Carbon;

final readonly class InMemoryEFtpRepository implements EFtpRepository
{
    private function __construct(
        private EFtps $Eftps
    ) {
    }

    /**
     * @return EFtps
     */
    public function findAllForActivityType(ActivityType $type): EFtps
    {
        return $this->Eftps->filter(fn (EFtp $eftp) => $eftp->getActivityType() === $type);
    }

    /**
     * @return ?EFtp
     */
    public function findForActivityType(SerializableDateTime $dateTime, ActivityType $type): ?EFtp
    {
        return $this->findAllForActivityType($type)->findForDate($dateTime);
    }

    /**
     * @return EFtpRepository
     */
    public static function fromActivities(Activities $allActivities): EFtpRepository
    {
        $eftps = [];
        
        foreach ($allActivities as $activity) {
            if ($activity->getEFTP() === null) {
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
