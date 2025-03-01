<?php

declare(strict_types=1);

namespace App\Tests\Domain\Strava\EFtp;

use App\Domain\Strava\Activity\Activities;
use App\Domain\Strava\Activity\Activity;
use App\Domain\Strava\Athlete\Weight\AthleteWeightRepository;
use App\Domain\Strava\EFtp\EFtpCalculator;
use App\Domain\Strava\EFtp\EFtpOutput;

final class EFtpCalculatorBuilder
{
    private Activities $activities;
    private AthleteWeightRepository $athleteWeightRepository;
    private int $numberOfMonths;

    private function __construct()
    {
        $this->activities = Activities::fromArray([]);
        $this->numberOfMonths = 0;
    }

    public static function fromDefaults(): self
    {
        return new self();
    }

    public function build(): EFtpCalculator
    {
        $calculator = EFtpCalculator::from($this->numberOfMonths, $this->athleteWeightRepository);
        $calculator->enrichWithActivities($this->activities);

        return $calculator;
    }

    public function withWeightRepository(AthleteWeightRepository $weightRepository): self
    {
        $this->athleteWeightRepository = $weightRepository;

        return $this;
    }

    public function withNumberOfMonths(int $numberOfMonths): self
    {
        $this->numberOfMonths = $numberOfMonths;

        return $this;
    }

    public function withActivityAndPowerOutput(Activity $activity, EFtpOutput $eftp): self
    {
        $activity->enrichWithEFTP($eftp);
        $this->activities->add($activity);

        return $this;
    }

    public function withActivityAndPower(Activity $activity, int $eftp): self
    {
        $activity->enrichWithEFTP(EFtpOutput::fromState(
            power: $eftp,
            timeIntervalInSeconds: 3600,
            formattedTimeInterval: '1 h',
            relativePower: 3
        ));
        $this->activities->add($activity);

        return $this;
    }
}
