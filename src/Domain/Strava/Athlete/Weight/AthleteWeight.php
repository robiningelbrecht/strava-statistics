<?php

declare(strict_types=1);

namespace App\Domain\Strava\Athlete\Weight;

use App\Domain\Measurement\Mass\Gram;
use App\Domain\Measurement\Mass\Kilogram;
use App\Infrastructure\ValueObject\Time\SerializableDateTime;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
final readonly class AthleteWeight
{
    private function __construct(
        #[ORM\Id, ORM\Column(type: 'date_immutable')]
        private SerializableDateTime $on,
        #[ORM\Column(type: 'integer')]
        private Gram $weightInGrams,
    ) {
    }

    public static function fromState(
        SerializableDateTime $on,
        Gram $weightInGrams,
    ): self {
        return new self(
            on: $on,
            weightInGrams: $weightInGrams
        );
    }

    public function getOn(): SerializableDateTime
    {
        return $this->on;
    }

    public function getWeightInGrams(): Gram
    {
        return $this->weightInGrams;
    }

    public function getWeightInKg(): Kilogram
    {
        return $this->getWeightInGrams()->toKilogram();
    }
}
