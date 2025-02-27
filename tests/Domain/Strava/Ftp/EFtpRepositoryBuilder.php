<?php

declare(strict_types=1);

namespace App\Tests\Domain\Strava\Ftp;

use App\Domain\Strava\Activity\Activities;
use App\Domain\Strava\Activity\Activity;
use App\Domain\Strava\Activity\Stream\PowerOutput;
use App\Domain\Strava\Ftp\InMemoryEFtpRepository;

final class EFtpRepositoryBuilder
{
    private Activities $activities;
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

    public function build(): InMemoryEFtpRepository
    {
        $repository = InMemoryEFtpRepository::from($this->numberOfMonths);
        $repository->enrichWithActivities($this->activities);

        return $repository;
    }

    public function withNumberOfMonths(int $numberOfMonths): self
    {
        $this->numberOfMonths = $numberOfMonths;

        return $this;
    }

    public function withActivityAndPowerOutput(Activity $activity, PowerOutput $eftp): self
    {
        $activity->enrichWithEFTP($eftp);
        $this->activities->add($activity);

        return $this;
    }

    public function withActivityAndPower(Activity $activity, int $eftp): self
    {
        $activity->enrichWithEFTP(PowerOutput::fromState(
            power: $eftp,
            timeIntervalInSeconds: 3600,
            formattedTimeInterval: '1 h',
            relativePower: 3
        ));
        $this->activities->add($activity);

        return $this;
    }
}
