<?php

declare(strict_types=1);

namespace App\Domain\Strava\Athlete;

use App\Infrastructure\ValueObject\Time\SerializableDateTime;

final readonly class Athlete
{
    private function __construct(
        private SerializableDateTime $birthDate,
    ) {
    }

    public static function create(SerializableDateTime $birthDate): self
    {
        return new self($birthDate);
    }

    public function getBirthDate(): SerializableDateTime
    {
        return $this->birthDate;
    }

    public function getAgeInYears(SerializableDateTime $on): int
    {
        return $this->getBirthDate()->diff($on)->y;
    }

    public function getMaxHeartRate(SerializableDateTime $on): int
    {
        return 220 - $this->getAgeInYears($on);
    }
}
