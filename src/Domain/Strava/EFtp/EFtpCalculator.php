<?php

namespace App\Domain\Strava\EFtp;

use App\Domain\Strava\Activity\Activities;
use App\Domain\Strava\Activity\Activity;
use App\Domain\Strava\Activity\ActivityType;
use App\Domain\Strava\Athlete\Weight\AthleteWeightRepository;
use App\Infrastructure\Exception\EntityNotFound;
use App\Infrastructure\ValueObject\Time\SerializableDateTime;
use Carbon\CarbonInterval;

final readonly class EFtpCalculator
{
    private EFtps $eftps;
    public const array EFTP_FACTORS = [
        300 => 0.79,
        600 => 0.86,
        900 => 0.92,
        1200 => 0.95,
        3600 => 1,
    ];

    public function __construct(
        private readonly AthleteWeightRepository $athleteWeightRepository,
        private readonly EFtpNumberOfMonths $numberOfMonths,
    ) {
        $this->eftps = EFtps::fromArray([]);
    }

    public function isEnabled(): bool
    {
        return $this->numberOfMonths->getNumberOfMonths() >= 1;
    }

    public function getNumberOfMonths(): int
    {
        return $this->numberOfMonths->getNumberOfMonths();
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
        if (false === $this->isEnabled()) {
            return null;
        }

        $eftps = $this->findAllForActivityType($type);
        $months = $this->numberOfMonths->getNumberOfMonths();

        $startDate = (clone $dateTime)->modify('-'.$months.' months')->setTime(0, 0, 0);
        $endDate = (clone $dateTime)->setTime(23, 59, 59);

        $maxEftp = null;

        $filtered = $eftps->filter(function (EFtp $eftp) use ($startDate, $endDate) {
            return $eftp->getSetOn() >= $startDate && $eftp->getSetOn() <= $endDate;
        });

        if ($filtered->isEmpty()) {
            return null;
        }

        foreach ($filtered as $eftp) {
            if (null === $maxEftp || $eftp->getEFtp() > $maxEftp->getEFtp()) {
                $maxEftp = $eftp;
            }
        }

        return $maxEftp;
    }

    public function enrichWithActivities(Activities $allActivities): void
    {
        foreach ($allActivities as $activity) {
            if (null === $activity->getEFtp()) {
                continue;
            }

            $this->eftps->add(EFtp::fromState(
                $activity->getStartDate(),
                $activity->getEFtp(),
                $activity->getSportType()->getActivityType()
            ));
        }
    }

    public function calculate(Activity $activity): ?EFtpOutput
    {
        $eftp = null;

        foreach (self::EFTP_FACTORS as $timeIntervalInSeconds => $factor) {
            $power = $activity->getBestAveragePowerForTimeInterval($timeIntervalInSeconds);

            if ($power) {
                $calculatedEFTP = (int) round($power->getPower() * $factor);

                try {
                    $athleteWeight = $this->athleteWeightRepository->find($activity->getStartDate())->getWeightInKg();
                } catch (EntityNotFound) {
                    throw new EntityNotFound(sprintf('Trying to calculate the relative power for activity "%s" on %s, but no corresponding athleteWeight was found. 
                    Make sure you configure the proper weights in your .env file. Do not forgot to run the app:strava:import-data command after changing the weights', $activity->getName(), $activity->getStartDate()->format('Y-m-d')));
                }

                if (null === $eftp || $calculatedEFTP > $eftp->getPower()) {
                    $interval = CarbonInterval::seconds($timeIntervalInSeconds);

                    $relativePower = 0;
                    $relativePower = $athleteWeight->toFloat() > 0
                        ? round($calculatedEFTP / $athleteWeight->toFloat(), 2)
                        : 0;

                    $time = (int) $interval->totalHours ? $interval->totalHours.' h' : ((int) $interval->totalMinutes ? $interval->totalMinutes.' m' : $interval->totalSeconds.' s');

                    $eftp = EFtpOutput::fromState(
                        formattedTimeInterval: sprintf('%s @ %d w', $time, $power->getPower()),
                        timeIntervalInSeconds: $timeIntervalInSeconds,
                        power: $calculatedEFTP,
                        relativePower: $relativePower,
                    );
                }
            }
        }

        return $eftp;
    }
}
