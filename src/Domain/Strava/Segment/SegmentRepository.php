<?php

declare(strict_types=1);

namespace App\Domain\Strava\Segment;

interface SegmentRepository
{
    public function add(Segment $segment): void;

    public function find(SegmentId $segmentId): Segment;

    public function findAll(): Segments;

    public function deleteOrphaned(): void;
}
