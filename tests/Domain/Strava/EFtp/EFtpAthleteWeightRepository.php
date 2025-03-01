<?php

declare(strict_types=1);

namespace App\Tests\Domain\Strava\EFtp;

use App\Domain\Strava\Athlete\Weight\AthleteWeight;
use App\Domain\Strava\Athlete\Weight\AthleteWeightRepository;
use App\Infrastructure\ValueObject\Measurement\Mass\Gram;
use App\Infrastructure\ValueObject\Time\SerializableDateTime;

final class EFtpAthleteWeightRepository implements AthleteWeightRepository
{
    private int $weight;

    public static function fromWeightInKg(int $weight): AthleteWeightRepository
    {
        $repository = new self();
        $repository->weight = $weight;

        return $repository;
    }

    public function removeAll(): void
    {
        $this->weight = 0;
    }

    public function save(AthleteWeight $weight): void
    {
        $this->weight = $weight->getWeightInKG();
    }

    public function find(SerializableDateTime $on): AthleteWeight
    {
        return AthleteWeight::fromState($on, Gram::from($this->weight * 1000));
    }

    public function build(): AthleteWeightRepository
    {
        return $this;
    }
}
