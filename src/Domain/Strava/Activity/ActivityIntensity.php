<?php

declare(strict_types=1);

namespace App\Domain\Strava\Activity;

use App\Domain\Strava\Athlete\AthleteRepository;
use App\Domain\Strava\EFtp\EFtpCalculator;
use App\Domain\Strava\Ftp\FtpRepository;
use App\Infrastructure\Exception\EntityNotFound;

final readonly class ActivityIntensity
{
    public function __construct(
        private AthleteRepository $athleteRepository,
        private FtpRepository $ftpRepository,
        private EFtpCalculator $eftpCalculator,
    ) {
    }

    private function calculateWithPower(int $movingTimeInSeconds, int $averagePower, int $ftp): int
    {
        // Use more complicated and more accurate calculation.
        // intensityFactor = averagePower / FTP
        // (durationInSeconds * averagePower * intensityFactor) / (FTP x 3600) * 100
        return (int) round(($movingTimeInSeconds * $averagePower * ($averagePower / $ftp)) / ($ftp * 3600) * 100);
    }

    private function calculateWithFTP(Activity $activity): ?int
    {
        try {
            $ftp = $this->ftpRepository->find($activity->getStartDate())->getFtp();
            if ($averagePower = $activity->getAveragePower()) {
                return $this->calculateWithPower(
                    $activity->getMovingTimeInSeconds(),
                    $averagePower,
                    $ftp->getValue()
                );
            }
        } catch (EntityNotFound) {
        }

        return null;
    }

    private function calculateWithEFTP(Activity $activity): ?int
    {
        if ($this->eftpCalculator->isEnabled()) {
            $eftp = $this->eftpCalculator->findForActivityType(
                $activity->getSportType()->getActivityType(),
                $activity->getStartDate()
            );

            if ($eftp && $averagePower = $activity->getAveragePower()) {
                return $this->calculateWithPower(
                    $activity->getMovingTimeInSeconds(),
                    $averagePower,
                    $eftp->getEFtp()
                );
            }
        }

        return null;
    }

    private function calculateWithHeartrate(Activity $activity): ?int
    {
        if ($averageHeartRate = $activity->getAverageHeartRate()) {
            $athlete = $this->athleteRepository->find();
            $athleteMaxHeartRate = $athlete->getMaxHeartRate($activity->getStartDate());
            // Use simplified, less accurate calculation.
            // maxHeartRate = = (220 - age) x 0.92
            // intensityFactor = averageHeartRate / maxHeartRate
            // (durationInSeconds x averageHeartRate x intensityFactor) / (maxHeartRate x 3600) x 100
            $maxHeartRate = round($athleteMaxHeartRate * 0.92);

            return (int) round(($activity->getMovingTimeInSeconds() * $averageHeartRate * ($averageHeartRate / $maxHeartRate)) / ($maxHeartRate * 3600) * 100);
        }

        return null;
    }

    public function calculate(Activity $activity): ?int
    {
        // To calculate intensity, we need
        // 1) eFTP and average power
        // OR
        // 2) FTP and average power
        // OR
        // 3) Max and average heart rate

        if ($eftpIntensity = $this->calculateWithEFTP($activity)) {
            return $eftpIntensity;
        }

        if ($ftpIntensity = $this->calculateWithFTP($activity)) {
            return $ftpIntensity;
        }

        if ($heartrateIntensity = $this->calculateWithHeartrate($activity)) {
            return $heartrateIntensity;
        }

        return null;
    }
}
