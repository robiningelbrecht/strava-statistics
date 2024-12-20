<?php

declare(strict_types=1);

namespace App\Tests\Domain\Strava\Segment\SegmentEffort;

use App\Domain\Strava\Activity\ActivityId;
use App\Domain\Strava\Segment\SegmentEffort\SegmentEffort;
use App\Domain\Strava\Segment\SegmentEffort\SegmentEffortId;
use App\Domain\Strava\Segment\SegmentId;
use App\Infrastructure\ValueObject\Time\SerializableDateTime;

final class SegmentEffortBuilder
{
    private SegmentEffortId $segmentEffortId;
    private SegmentId $segmentId;
    private ActivityId $activityId;
    private readonly SerializableDateTime $startDateTime;
    private array $data;

    private function __construct()
    {
        $this->segmentEffortId = SegmentEffortId::fromUnprefixed('1');
        $this->segmentId = SegmentId::fromUnprefixed('1');
        $this->activityId = ActivityId::fromUnprefixed('1');
        $this->startDateTime = SerializableDateTime::fromString('2023-10-10');
        $this->data = [];
    }

    public static function fromDefaults(): self
    {
        return new self();
    }

    public function build(): SegmentEffort
    {
        return SegmentEffort::fromState(
            segmentEffortId: $this->segmentEffortId,
            segmentId: $this->segmentId,
            activityId: $this->activityId,
            startDateTime: $this->startDateTime,
            data: $this->data,
        );
    }

    public function withId(SegmentEffortId $id): self
    {
        $this->segmentEffortId = $id;

        return $this;
    }

    public function withSegmentId(SegmentId $id): self
    {
        $this->segmentId = $id;

        return $this;
    }

    public function withActivityId(ActivityId $activityId): self
    {
        $this->activityId = $activityId;

        return $this;
    }

    public function withData(array $data): self
    {
        $this->data = $data;

        return $this;
    }
}
