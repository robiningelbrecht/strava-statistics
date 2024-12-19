<?php

declare(strict_types=1);

namespace App\Domain\Strava\Athlete\Weight\ImportAthleteWeight;

use App\Domain\Strava\Athlete\Weight\AthleteWeight;
use App\Domain\Strava\Athlete\Weight\AthleteWeights;
use App\Infrastructure\Serialization\Json;
use App\Infrastructure\ValueObject\Time\SerializableDateTime;

final readonly class AthleteWeightsFromEnvFile
{
    private AthleteWeights $weights;

    /**
     * @param array<string, float> $weightsInKg
     */
    private function __construct(
        array $weightsInKg,
    ) {
        $this->weights = AthleteWeights::empty();

        foreach ($weightsInKg as $on => $weightInKg) {
            $this->weights->add(AthleteWeight::fromState(
                on: SerializableDateTime::fromString($on),
                weightInGrams: (int) ($weightInKg * 1000),
            ));
        }
    }

    public function getAll(): AthleteWeights
    {
        return $this->weights;
    }

    public static function fromString(string $values): self
    {
        return new self(
            Json::decode($values)
        );
    }
}
