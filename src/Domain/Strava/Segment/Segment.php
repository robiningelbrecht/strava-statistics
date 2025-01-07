<?php

declare(strict_types=1);

namespace App\Domain\Strava\Segment;

use App\Domain\Measurement\Length\Kilometer;
use App\Domain\Strava\Segment\SegmentEffort\SegmentEffort;
use App\Domain\Strava\SportType;
use App\Infrastructure\ValueObject\String\Name;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
final class Segment
{
    private ?SegmentEffort $bestEffort = null;
    private int $numberOfTimesRidden = 0;
    private ?string $deviceName = null;
    private ?SportType $sportType = null;

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
        $parts = [];
        if($this->isStarred()){
            $parts[]= '⭐️';
        }
        if ($this->isKOM()) {
            $parts[]= '🏔️';
        }
        $parts[] = $this->name;

        return Name::fromString(implode(' ', $parts));
    }

    public function getDistance(): Kilometer
    {
        return Kilometer::from($this->data['distance'] / 1000);
    }

    public function getMaxGradient(): float
    {
        return $this->data['maximum_grade'];
    }

    public function getSportType(): ?SportType
    {
        return $this->sportType;
    }

    public function enrichWithSportType(SportType $sportType): void
    {
        $this->sportType = $sportType;
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

    /**
     * @return string[]
     */
    public function getSearchables(): array
    {
        return array_filter([
            (string) $this->getName(),
        ]);
    }

    /**
     * @return array<string, bool>
     */
    public function getFilterables(): array
    {
        return [
            'isKom' => $this->isKOM(),
            'isFavourite' => $this->isStarred(),
            'sportType' => $this->getSportType()->value,
        ];
    }

    /**
     * @return array<string, string|int|float>
     */
    public function getSortables(): array
    {
        return array_filter([
            'name' => (string) $this->getName(),
            'distance' => round($this->getDistance()->toFloat(), 2),
            'max-gradient' => $this->getMaxGradient(),
            'ride-count' => $this->getNumberOfTimesRidden(),
        ]);
    }

    public function isKOM(): bool
    {
        $komSegmentIds = [
            12128917,
            22813206,
            17267489,
            24700976,
            24701010,
            33620168,
            38170246,
            12744502,
            28433453,
            16784833,
            16784850,
            16802545,
            12109030,
            12128029,
            18397965,
            18389384,
            37039571,
            38138480,
            38132913,
            26935782,
            38147800,
            16781407,
            16781411,
            12128826,
            26935782,
            37049451,
            24682578,
            19141090,
            19141092,
            24690967,
            14120182,
            30407861,
            32762879,
            33636401,
            33636430,
            28432293,
            28432259,
            38170244,
            33636632,
            37033150,
            21343975,
            21343961,
            14270131,
            21747822,
            21747891,
            18389384,
        ];

        return in_array((int) $this->getId()->toUnprefixedString(), $komSegmentIds);
    }
}
