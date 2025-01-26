<?php

namespace App\Tests\Domain\Strava\Activity\Split;

use App\Infrastructure\ValueObject\Measurement\Velocity\MetersPerSecond;
use PHPUnit\Framework\TestCase;

class ActivitySplitTest extends TestCase
{
    public function testGetRelativePacePercentage(): void
    {
        $activitySplit = ActivitySplitBuilder::fromDefaults()
            ->withAverageSpeed(MetersPerSecond::from(10))
            ->withMaxAverageSpeed(MetersPerSecond::from(15))
            ->build();

        $this->assertEquals(66.67, $activitySplit->getRelativePacePercentage());
    }
}
