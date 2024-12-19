<?php

declare(strict_types=1);

namespace App\Tests\Domain\Strava\Athlete\Weight;

use App\Domain\Strava\Athlete\Weight\AthleteWeight;
use App\Infrastructure\ValueObject\Time\SerializableDateTime;

final class AthleteWeightBuilder
{
    private SerializableDateTime $on;
    private int $weightInGrams;

    public function __construct()
    {
        $this->on = SerializableDateTime::fromString('18-12-2024');
        $this->weightInGrams = 74600;
    }

    public static function fromDefaults(): self
    {
        return new self();
    }

    public function build(): AthleteWeight
    {
        return AthleteWeight::fromState(
            on: $this->on,
            weightInGrams: $this->weightInGrams
        );
    }

    public function withOn(SerializableDateTime $on): self
    {
        $this->on = $on;

        return $this;
    }

    public function withWeightInGrams(int $weightInGrams): self
    {
        $this->weightInGrams = $weightInGrams;

        return $this;
    }
}