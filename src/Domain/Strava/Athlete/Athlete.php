<?php

declare(strict_types=1);

namespace App\Domain\Strava\Athlete;

use App\Infrastructure\ValueObject\String\Name;
use App\Infrastructure\ValueObject\Time\SerializableDateTime;

final readonly class Athlete implements \JsonSerializable
{
    private function __construct(
        /** @var array<string, mixed> */
        private array $data,
    ) {
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function create(
        array $data,
    ): self {
        return new self(
            data: $data,
        );
    }

    public function getAthleteId(): string
    {
        return (string) $this->data['id'];
    }

    public function getBirthDate(): SerializableDateTime
    {
        return SerializableDateTime::fromString($this->data['birthDate']);
    }

    public function getAgeInYears(SerializableDateTime $on): int
    {
        return $this->getBirthDate()->diff($on)->y;
    }

    public function getMaxHeartRate(SerializableDateTime $on): int
    {
        return 220 - $this->getAgeInYears($on);
    }

    public function getName(): Name
    {
        return Name::fromString(sprintf('%s %s', $this->data['firstname'] ?? 'John', $this->data['lastname'] ?? 'Doe'));
    }

    /**
     * @return array<string, mixed>
     */
    public function jsonSerialize(): array
    {
        return $this->data;
    }
}
