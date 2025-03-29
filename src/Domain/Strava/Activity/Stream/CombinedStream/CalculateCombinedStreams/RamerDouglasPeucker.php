<?php

declare(strict_types=1);

namespace App\Domain\Strava\Activity\Stream\CombinedStream\CalculateCombinedStreams;

use App\Domain\Strava\Activity\ActivityType;
use App\Domain\Strava\Activity\Stream\ActivityStream;
use App\Domain\Strava\Activity\Stream\ActivityStreams;
use App\Domain\Strava\Activity\Stream\StreamType;

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
    public function apply(): array
    {
        // Calculate epsilon to determine level of simplification we want to apply.
        if (!$distances = $this->distanceStream->getData()) {
            throw new \InvalidArgumentException('Distance stream is empty');
        }
        if (!$altitudes = $this->altitudeStream->getData()) {
            throw new \InvalidArgumentException('Altitude stream is empty');
        }
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

        $keys = array_merge(
            [StreamType::DISTANCE->value, StreamType::ALTITUDE->value],
            array_map(fn (ActivityStream $stream) => $stream->getStreamType()->value, $this->otherStreams->toArray())
        );

        return array_map(
            fn (array $points) => array_combine($keys, $points),
            $this->simplify($rawPoints, $epsilon)
        );
    }

    /**
     * @param array<int, array<int, int|float>> $points,
     *
     * @return array<mixed>
     */
    private function simplify(array $points, float $epsilon): array
    {
        if (count($points) < 3) {
            return $points;
        }

        $dMax = 0;
        $index = 0;
        $end = count($points) - 1;

        for ($i = 1; $i < $end; ++$i) {
            $d = $this->getPointToLineDistance($points[$i], $points[0], $points[$end]);
            if ($d > $dMax) {
                $index = $i;
                $dMax = $d;
            }
        }

        if ($dMax > $epsilon) {
            $firstHalf = $this->simplify(array_slice($points, 0, $index + 1), $epsilon);
            $secondHalf = $this->simplify(array_slice($points, $index), $epsilon);

            return array_merge(array_slice($firstHalf, 0, -1), $secondHalf);
        }

        return [$points[0], $points[$end]];
    }

    /**
     * @param array<int, int|float> $point
     * @param array<int, int|float> $lineStart
     * @param array<int, int|float> $lineEnd
     */
    private function getPointToLineDistance(
        array $point,
        array $lineStart,
        array $lineEnd): float
    {
        $lineVector = [];
        $pointVector = [];

        for ($i = 0; $i < count($point); ++$i) {
            $lineVector[] = $lineEnd[$i] - $lineStart[$i];
            $pointVector[] = $point[$i] - $lineStart[$i];
        }

        $dotProduct = array_sum(array_map(fn (float $a, float $b) => $a * $b, $pointVector, $lineVector));
        $lineLengthSq = array_sum(array_map(fn (float $a) => $a * $a, $lineVector));

        $t = (0 != $lineLengthSq) ? $dotProduct / $lineLengthSq : 0;
        $t = max(0, min(1, $t)); // Clamp to segment bounds

        $closestPoint = [];
        for ($i = 0; $i < count($point); ++$i) {
            $closestPoint[] = $lineStart[$i] + $t * $lineVector[$i];
        }

        return sqrt(array_sum(array_map(fn (float $a, float $b) => ($a - $b) ** 2, $point, $closestPoint)));
    }
}
