<?php

declare(strict_types=1);

namespace App\Domain\Strava\Athlete\Weight\ImportAthleteWeight;

use App\Domain\Measurement\Mass\Kilogram;
use App\Domain\Measurement\Mass\Pound;
use App\Domain\Measurement\UnitSystem;
use App\Domain\Strava\Athlete\Weight\AthleteWeight;
use App\Domain\Strava\Athlete\Weight\AthleteWeights;
use App\Infrastructure\Serialization\Json;
use App\Infrastructure\ValueObject\Time\SerializableDateTime;

final readonly class AthleteWeightsFromEnvFile
{
    private AthleteWeights $weights;

    /**
     * @param array<string, float> $weightsFromEnv
     */
    private function __construct(
        array $weightsFromEnv,
        private UnitSystem $unitSystem,
    ) {
        $this->weights = AthleteWeights::empty();

        foreach ($weightsFromEnv as $on => $weight) {
            $weightInGrams = Kilogram::from($weight)->toGram();
            if (UnitSystem::IMPERIAL === $this->unitSystem) {
                $weightInGrams = Pound::from($weight)->toGram();
            }

            $this->weights->add(AthleteWeight::fromState(
                on: SerializableDateTime::fromString($on),
                weightInGrams: $weightInGrams,
            ));
        }
    }

    public function getAll(): AthleteWeights
    {
        return $this->weights;
    }

    public static function fromString(string $values, UnitSystem $unitSystem): self
    {
        return new self(
            weightsFromEnv: Json::decode($values),
            unitSystem: $unitSystem
        );
    }
}
