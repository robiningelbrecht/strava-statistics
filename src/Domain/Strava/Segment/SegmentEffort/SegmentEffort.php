<?php

declare(strict_types=1);

namespace App\Domain\Strava\Segment\SegmentEffort;

use App\Domain\Measurement\Length\Kilometer;
use App\Domain\Measurement\Velocity\KmPerHour;
use App\Domain\Strava\Activity\ActivityId;
use App\Domain\Strava\Activity\ReadModel\ActivityDetails;
use App\Domain\Strava\Segment\SegmentId;
use App\Infrastructure\Time\Format\ProvideTimeFormats;
use App\Infrastructure\ValueObject\Time\SerializableDateTime;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Index(name: 'SegmentEffort_segmentIndex', columns: ['segmentId'])]
#[ORM\Index(name: 'SegmentEffort_activityIndex', columns: ['activityId'])]
final class SegmentEffort
{
    use ProvideTimeFormats;

    private ?ActivityDetails $activity = null;

    /**
     * @param array<mixed> $data
     */
    private function __construct(
        #[ORM\Id, ORM\Column(type: 'string', unique: true)]
        private readonly SegmentEffortId $segmentEffortId,
        #[ORM\Column(type: 'string')]
        private readonly SegmentId $segmentId,
        #[ORM\Column(type: 'string')]
        private readonly ActivityId $activityId,
        #[ORM\Column(type: 'datetime_immutable')]
        private readonly SerializableDateTime $startDateTime,
        #[ORM\Column(type: 'json')]
        private readonly array $data,
    ) {
    }

    /**
     * @param array<mixed> $data
     */
    public static function create(
        SegmentEffortId $segmentEffortId,
        SegmentId $segmentId,
        ActivityId $activityId,
        SerializableDateTime $startDateTime,
        array $data,
    ): self {
        return new self(
            segmentEffortId: $segmentEffortId,
            segmentId: $segmentId,
            activityId: $activityId,
            startDateTime: $startDateTime,
            data: $data,
        );
    }

    /**
     * @param array<mixed> $data
     */
    public static function fromState(
        SegmentEffortId $segmentEffortId,
        SegmentId $segmentId,
        ActivityId $activityId,
        SerializableDateTime $startDateTime,
        array $data,
    ): self {
        return new self(
            segmentEffortId: $segmentEffortId,
            segmentId: $segmentId,
            activityId: $activityId,
            startDateTime: $startDateTime,
            data: $data,
        );
    }

    public function getId(): SegmentEffortId
    {
        return $this->segmentEffortId;
    }

    public function getSegmentId(): SegmentId
    {
        return $this->segmentId;
    }

    public function getActivityId(): ActivityId
    {
        return $this->activityId;
    }

    public function getName(): string
    {
        return $this->data['name'];
    }

    public function getStartDateTime(): SerializableDateTime
    {
        return $this->startDateTime;
    }

    public function getElapsedTimeInSeconds(): float
    {
        return (float) $this->data['elapsed_time'];
    }

    public function getElapsedTimeFormatted(): string
    {
        return $this->formatDurationForHumans((int) round($this->getElapsedTimeInSeconds()));
    }

    public function getAverageWatts(): ?float
    {
        if (isset($this->data['average_watts'])) {
            return (float) $this->data['average_watts'];
        }

        return null;
    }

    public function getAverageSpeed(): KmPerHour
    {
        $averageSpeed = $this->data['distance'] / $this->getElapsedTimeInSeconds();

        return KmPerHour::from($averageSpeed * 3.6);
    }

    public function getDistance(): Kilometer
    {
        return Kilometer::from($this->data['distance'] / 1000);
    }

    /**
     * @return array<mixed>
     */
    public function getData(): array
    {
        return $this->data;
    }

    public function getActivity(): ?ActivityDetails
    {
        return $this->activity;
    }

    public function enrichWithActivity(ActivityDetails $activity): void
    {
        $this->activity = $activity;
    }
}
