<?php

declare(strict_types=1);

namespace App\Tests\Domain\Strava\Segment;

use App\Domain\Strava\Segment\Segment;
use App\Domain\Strava\Segment\SegmentId;
use App\Infrastructure\ValueObject\String\Name;

final class SegmentBuilder
{
    private SegmentId $segmentId;
    private Name $name;
    private array $data;

    private function __construct()
    {
        $this->segmentId = SegmentId::fromUnprefixed('1');
        $this->name = Name::fromString('Segment');
        $this->data = [];
    }

    public static function fromDefaults(): self
    {
        return new self();
    }

    public function build(): Segment
    {
        return Segment::fromState(
            segmentId: $this->segmentId,
            name: $this->name,
            data: $this->data,
        );
    }

    public function withSegmentId(SegmentId $id): self
    {
        $this->segmentId = $id;

        return $this;
    }

    public function withName(Name $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function withData(array $data): self
    {
        $this->data = $data;

        return $this;
    }
}
