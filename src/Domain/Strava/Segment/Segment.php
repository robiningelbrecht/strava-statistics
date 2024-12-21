<?php

declare(strict_types=1);

namespace App\Domain\Strava\Segment;

use App\Domain\Measurement\Length\Kilometer;
use App\Domain\Strava\Activity\ActivityType;
use App\Domain\Strava\Segment\SegmentEffort\SegmentEffort;
use App\Infrastructure\ValueObject\String\Name;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
final class Segment
{
    private ?SegmentEffort $bestEffort = null;
    private int $numberOfTimesRidden = 0;
    private ?string $deviceName = null;

    /**
     * @param array<mixed> $data
     */
    private function __construct(
        #[ORM\Id, ORM\Column(type: 'string', unique: true)]
        private readonly SegmentId $segmentId,
        #[ORM\Column(type: 'string', nullable: true)]
        private readonly Name $name,
        #[ORM\Column(type: 'json')]
        private array $data,
    ) {
    }

    /**
     * @param array<mixed> $data
     */
    public static function create(
        SegmentId $segmentId,
        Name $name,
        array $data,
    ): self {
        return new self(
            segmentId: $segmentId,
            name: $name,
            data: $data,
        );
    }

    /**
     * @param array<mixed> $data
     */
    public static function fromState(
        SegmentId $segmentId,
        Name $name,
        array $data,
    ): self {
        return new self(
            segmentId: $segmentId,
            name: $name,
            data: $data,
        );
    }

    public function getId(): SegmentId
    {
        return $this->segmentId;
    }

    public function getName(): Name
    {
        return $this->name;
    }

    public function getDistance(): Kilometer
    {
        return Kilometer::from($this->data['distance'] / 1000);
    }

    public function getMaxGradient(): float
    {
        return $this->data['maximum_grade'];
    }

    public function getActivityType(): ActivityType
    {
        return ActivityType::from($this->data['activity_type']);
    }

    public function isZwiftSegment(): bool
    {
        return 'zwift' === strtolower($this->deviceName ?? '');
    }

    public function isRouvySegment(): bool
    {
        return 'rouvy' === strtolower($this->deviceName ?? '');
    }

    public function enrichWithDeviceName(?string $deviceName): void
    {
        $this->deviceName = $deviceName;
    }

    /**
     * @return array<mixed>
     */
    public function getData(): array
    {
        return $this->data;
    }

    /**
     * @return string[]
     */
    public function getSearchables(): array
    {
        return array_filter([
            (string) $this->getName(),
            $this->isStarred() ? 'favourite starred' : null]
        );
    }

    public function getBestEffort(): ?SegmentEffort
    {
        return $this->bestEffort;
    }

    public function enrichWithBestEffort(SegmentEffort $segmentEffort): void
    {
        $this->bestEffort = $segmentEffort;
    }

    public function getNumberOfTimesRidden(): int
    {
        return $this->numberOfTimesRidden;
    }

    public function enrichWithNumberOfTimesRidden(int $numberOfTimesRidden): void
    {
        $this->numberOfTimesRidden = $numberOfTimesRidden;
    }

    public function isStarred(): bool
    {
        if (!isset($this->data['starred'])) {
            return false;
        }

        return (bool) $this->data['starred'];
    }
}
