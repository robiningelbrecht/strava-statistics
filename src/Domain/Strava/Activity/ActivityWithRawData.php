<?php

declare(strict_types=1);

namespace App\Domain\Strava\Activity;

use App\Infrastructure\ValueObject\Measurement\UnitSystem;

final readonly class ActivityWithRawData
{
    /**
     * @param array<mixed> $rawData
     */
    private function __construct(
        private Activity $activity,
        private array $rawData,
    ) {
    }

    /**
     * @param array<mixed> $rawData
     */
    public static function fromState(
        Activity $activity,
        array $rawData,
    ): self {
        return new self(
            activity: $activity,
            rawData: $rawData
        );
    }

    public function getActivity(): Activity
    {
        return $this->activity;
    }

    /**
     * @return array<mixed>
     */
    public function getRawData(): array
    {
        return $this->rawData;
    }

    /**
     * @return array<mixed>
     */
    public function getSegmentEfforts(): array
    {
        return $this->rawData['segment_efforts'] ?? [];
    }

    /**
     * @return array<mixed>
     */
    public function getSplits(): array
    {
        return array_merge(
            array_map(
                fn (array $split) => array_merge($split, ['unit_system' => UnitSystem::METRIC->value]),
                $this->rawData['splits_metric'] ?? [],
            ),
            array_map(
                fn (array $split) => array_merge($split, ['unit_system' => UnitSystem::IMPERIAL->value]),
                $this->rawData['splits_standard'] ?? [],
            )
        );
    }
}
