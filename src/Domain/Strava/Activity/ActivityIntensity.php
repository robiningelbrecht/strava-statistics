<?php

declare(strict_types=1);

namespace App\Domain\Strava\Activity;

use App\Domain\Strava\Athlete\AthleteRepository;
use App\Domain\Strava\Ftp\EFtpRepository;

final class ActivityIntensity
{
    private EFtpRepository $eftpRepository;

    public function __construct(
        private AthleteRepository $athleteRepository,
    ) {
    }

    public function setEftpRepository(EFtpRepository $eftpRepository): void
    {
        $this->eftpRepository = $eftpRepository;
    }

    public function calculate(Activity $activity): ?int
    {
        $athlete = $this->athleteRepository->find();
        $ftp = $this->eftpRepository->findForActivityType(
            $activity->getStartDate(),
            $activity->getSportType()->getActivityType()
        );

        // To calculate intensity, we need
        // 1) Max and average heart rate
        // OR
        // 2) FTP and average power
        if ($ftp && $averagePower = $activity->getAveragePower()) {
            // Use more complicated and more accurate calculation.
            // intensityFactor = averagePower / FTP
            // (durationInSeconds * averagePower * intensityFactor) / (FTP x 3600) * 100
            return (int) round(($activity->getMovingTimeInSeconds() * $averagePower * ($averagePower / $ftp->getEftp())) / ($ftp->getEftp() * 3600) * 100);
        }

        if ($averageHeartRate = $activity->getAverageHeartRate()) {
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
}
