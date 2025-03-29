<?php

declare(strict_types=1);

namespace App\Domain\Strava\Activity\Stream\CombinedStream;

use App\Domain\Strava\Activity\ActivityId;
use App\Infrastructure\ValueObject\Measurement\UnitSystem;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
final readonly class CombinedActivityStream
{
    /**
     * @param array<mixed> $data
     */
    private function __construct(
        #[ORM\Id, ORM\Column(type: 'string')]
        private ActivityId $activityId,
        #[ORM\Id, ORM\Column(type: 'string')]
        private UnitSystem $unitSystem,
        #[ORM\Column(type: 'json')]
        private array $data,
    ) {
    }

    /**
     * @param array<mixed> $data
     */
    public static function create(
        ActivityId $activityId,
        UnitSystem $unitSystem,
        array $data,
    ): self {
        return new self(
            activityId: $activityId,
            unitSystem: $unitSystem,
            data: $data,
        );
    }

    /**
     * @param array<mixed> $data
     */
    public static function fromState(
        ActivityId $activityId,
        UnitSystem $unitSystem,
        array $data,
    ): self {
        return new self(
            activityId: $activityId,
            unitSystem: $unitSystem,
            data: $data,
        );
    }

    public function getActivityId(): ActivityId
    {
        return $this->activityId;
    }

    public function getUnitSystem(): UnitSystem
    {
        return $this->unitSystem;
    }

    public function getData(): array
    {
        return $this->data;
    }
}
