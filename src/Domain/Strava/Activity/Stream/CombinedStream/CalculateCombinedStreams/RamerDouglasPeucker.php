<?php

declare(strict_types=1);

namespace App\Domain\Strava\Activity\Stream\CombinedStream\CalculateCombinedStreams;

use App\Domain\Strava\Activity\ActivityType;
use App\Domain\Strava\Activity\Stream\ActivityStream;
use App\Domain\Strava\Activity\Stream\ActivityStreams;

final readonly class RamerDouglasPeucker
{
    public function __construct(
        private ActivityType $activityType,
        private ActivityStream $distanceStream,
        private ActivityStream $altitudeStream,
        private ActivityStreams $otherStreams,
    ) {
    }

    /**
     * @return array<mixed>
     */
    public function applyAlgorithm(): array
    {
        // Calculate epsilon to determine level of simplification we want to apply.
        $distances = $this->distanceStream->getData();
        $altitudes = $this->altitudeStream->getData();
        $totalDistance = end($distances);
        $elevationVariance = max($altitudes) - min($altitudes);

        $baseEpsilon = match ($this->activityType) {
            ActivityType::RUN => 0.7,
            ActivityType::WALK => 0.5,
            default => 1.0,
        };

        // Adjust based on distance, elevation and activity type.
        $epsilon = min(3.0, max(0.5, $baseEpsilon + ($totalDistance / 1000) + ($elevationVariance / 1000)));

        $rawPoints = [];
        foreach ($distances as $i => $distance) {
            $otherPoints = [];
            foreach ($this->otherStreams as $otherStream) {
                $otherPoints[] = $otherStream->getData()[$i] ?? 0;
            }

            $rawPoints[] = [
                $distance,
                $altitudes[$i] ?? 0,
                ...$otherPoints,
            ];
        }

        return $this->rdpSimplifyMulti($rawPoints, $epsilon);
    }

    /**
     * @return array<mixed>
     */
    private function rdpSimplifyMulti($points, $epsilon): array
    {
        if (count($points) < 3) {
            return $points;
        }

        $dMax = 0;
        $index = 0;
        $end = count($points) - 1;

        for ($i = 1; $i < $end; ++$i) {
            $d = $this->pointToLineDistance($points[$i], $points[0], $points[$end]);
            if ($d > $dMax) {
                $index = $i;
                $dMax = $d;
            }
        }

        if ($dMax > $epsilon) {
            $firstHalf = $this->rdpSimplifyMulti(array_slice($points, 0, $index + 1), $epsilon);
            $secondHalf = $this->rdpSimplifyMulti(array_slice($points, $index), $epsilon);

            return array_merge(array_slice($firstHalf, 0, -1), $secondHalf);
        }

        return [$points[0], $points[$end]];
    }

    private function pointToLineDistance($point, $lineStart, $lineEnd): float|int
    {
        $lineVector = [];
        $pointVector = [];

        for ($i = 0; $i < count($point); ++$i) {
            $lineVector[] = $lineEnd[$i] - $lineStart[$i];
            $pointVector[] = $point[$i] - $lineStart[$i];
        }

        $dotProduct = array_sum(array_map(fn ($a, $b) => $a * $b, $pointVector, $lineVector));
        $lineLengthSq = array_sum(array_map(fn ($a) => $a * $a, $lineVector));

        $t = (0 != $lineLengthSq) ? $dotProduct / $lineLengthSq : 0;
        $t = max(0, min(1, $t)); // Clamp to segment bounds

        $closestPoint = [];
        for ($i = 0; $i < count($point); ++$i) {
            $closestPoint[] = $lineStart[$i] + $t * $lineVector[$i];
        }

        return sqrt(array_sum(array_map(fn ($a, $b) => ($a - $b) ** 2, $point, $closestPoint)));
    }
}
