<?php

namespace App\Domain\Strava\Ftp;

use App\Domain\Strava\Activity\Activities;
use App\Domain\Strava\Activity\ActivityType;
use App\Infrastructure\ValueObject\Time\SerializableDateTime;

final readonly class InMemoryEFtpRepository implements EFtpRepository
{
    private EFtps $eftps;

    private function __construct(
        private int $numberOfMonths,
    ) {
        $this->eftps = EFtps::fromArray([]);
    }

    public function enabled(): bool
    {
        return $this->numberOfMonths >= 1;
    }

    public function getNumberOfMonths(): int
    {
        return $this->numberOfMonths;
    }

    public function findAllForActivityType(ActivityType $type): EFtps
    {
        return $this->eftps->filter(fn (EFtp $eftp) => $eftp->getActivityType() === $type);
    }

    /**
     * @return array<string>
     */
    public function findEFtpDates(ActivityType $type): array
    {
        $eftps = $this->findAllForActivityType($type);

        return $eftps->map(function (EFtp $eftp) {
            return $eftp->getSetOn()->format('Y-m-d');
        });
    }

    public function findForActivityType(ActivityType $type, SerializableDateTime $dateTime): ?EFtp
    {
        if (false === $this->enabled()) {
            return null;
        }

        $eftps = $this->findAllForActivityType($type);

        $startDate = (clone $dateTime)->modify('-'.$this->numberOfMonths.' months')->setTime(0, 0, 0);
        $endDate = (clone $dateTime)->setTime(23, 59, 59);

        $maxEftp = null;

        $filtered = $eftps->filter(function (EFtp $eftp) use ($startDate, $endDate) {
            return $eftp->getSetOn() >= $startDate && $eftp->getSetOn() <= $endDate;
        });

        if ($filtered->isEmpty()) {
            return null;
        }

        foreach ($filtered as $eftp) {
            if (null === $maxEftp || $eftp->getEftp() > $maxEftp->getEftp()) {
                $maxEftp = $eftp;
            }
        }

        return $maxEftp;
    }

    public function enrichWithActivities(Activities $allActivities): void
    {
        foreach ($allActivities as $activity) {
            if (null === $activity->getEFTP()) {
                continue;
            }

            $this->eftps->add(EFtp::fromState(
                $activity->getStartDate(),
                $activity->getEFTP(),
                $activity->getSportType()->getActivityType()
            ));
        }
    }

    public static function from(?int $numberOfMonths): self
    {
        if (null === $numberOfMonths) {
            $numberOfMonths = 0;
        }

        return new self($numberOfMonths);
    }
}
