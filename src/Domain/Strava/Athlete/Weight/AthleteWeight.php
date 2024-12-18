<?php

declare(strict_types=1);

namespace App\Domain\Strava\Athlete\Weight;

use App\Infrastructure\ValueObject\Time\SerializableDateTime;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
final readonly class AthleteWeight
{
    private function __construct(
        #[ORM\Id, ORM\Column(type: 'date_immutable')]
        private SerializableDateTime $on,
        #[ORM\Column(type: 'integer')]
        private int $weightInGrams,
    ) {
    }

    public static function fromState(
        SerializableDateTime $on,
        int $weightInGrams,
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

    public function getWeightInGrams(): int
    {
        return $this->weightInGrams;
    }

    public function getWeightInKg(): float
    {
        return round($this->weightInGrams / 1000, 2);
    }
}
