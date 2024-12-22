<?php

namespace App\Domain\Strava\Gear;

use App\Domain\Measurement\Length\Kilometer;
use App\Infrastructure\ValueObject\Time\SerializableDateTime;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
class Gear
{
    /**
     * @param array<mixed> $data
     */
    private function __construct(
        #[ORM\Id, ORM\Column(type: 'string', unique: true)]
        private readonly GearId $gearId,
        #[ORM\Column(type: 'datetime_immutable')]
        private readonly SerializableDateTime $createdOn,
        #[ORM\Column(type: 'integer')]
        private int $distanceInMeter,
        #[ORM\Column(type: 'json')]
        private array $data,
    ) {
    }

    /**
     * @param array<mixed> $data
     */
    public static function create(
        GearId $gearId,
        array $data,
        int $distanceInMeter,
        SerializableDateTime $createdOn,
    ): self {
        return new self(
            gearId: $gearId,
            createdOn: $createdOn,
            distanceInMeter: $distanceInMeter,
            data: $data
        );
    }

    /**
     * @param array<mixed> $data
     */
    public static function fromState(
        GearId $gearId,
        array $data,
        int $distanceInMeter,
        SerializableDateTime $createdOn,
    ): self {
        return new self(
            gearId: $gearId,
            createdOn: $createdOn,
            distanceInMeter: $distanceInMeter,
            data: $data
        );
    }

    public function getId(): GearId
    {
        return $this->gearId;
    }

    public function getName(): string
    {
        return sprintf('%s%s', $this->data['name'], $this->isRetired() ? ' ☠️' : '');
    }

    public function getDistance(): Kilometer
    {
        return Kilometer::from($this->distanceInMeter / 1000);
    }

    public function isRetired(): bool
    {
        return $this->data['retired'] ?? false;
    }

    public function updateIsRetired(bool $isRetired): self
    {
        $this->data['retired'] = $isRetired;

        return $this;
    }

    public function updateDistance(float $distance, float $convertedDistance): self
    {
        $this->distanceInMeter = (int) $distance;
        $this->data['converted_distance'] = $convertedDistance;

        return $this;
    }

    public function getCreatedOn(): SerializableDateTime
    {
        return $this->createdOn;
    }

    /**
     * @return array<mixed>
     */
    public function getData(): array
    {
        return $this->data;
    }
}
