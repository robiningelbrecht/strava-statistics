<?php

namespace App\Tests\Domain\Strava\Activity\Split;

use App\Domain\Strava\Activity\Split\ActivitySplit;
use App\Infrastructure\ValueObject\Measurement\Velocity\MetersPerSecond;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class ActivitySplitTest extends TestCase
{
    #[DataProvider(methodName: 'provideRelativePacePercentages')]
    public function testGetRelativePacePercentage(
        ActivitySplit $activitySplit,
        float $expectedPercentage): void
    {
        $this->assertEquals($expectedPercentage, $activitySplit->getRelativePacePercentage());
    }

    public static function provideRelativePacePercentages(): array
    {
        return [
            [
                ActivitySplitBuilder::fromDefaults()
                    ->withAverageSpeed(MetersPerSecond::from(13.3))
                    ->withMinAverageSpeed(MetersPerSecond::from(0))
                    ->withMaxAverageSpeed(MetersPerSecond::from(100))
                    ->build(),
                83.38,
            ],
            [
                ActivitySplitBuilder::fromDefaults()
                    ->withAverageSpeed(MetersPerSecond::from(3.6))
                    ->withMinAverageSpeed(MetersPerSecond::from(2.4))
                    ->withMaxAverageSpeed(MetersPerSecond::from(4.8))
                    ->build(),
                20.0,
            ],
            [
                ActivitySplitBuilder::fromDefaults()
                    ->withAverageSpeed(MetersPerSecond::from(13.3))
                    ->withMinAverageSpeed(MetersPerSecond::from(20))
                    ->withMaxAverageSpeed(MetersPerSecond::from(0))
                    ->build(),
                0,
            ],
            [
                ActivitySplitBuilder::fromDefaults()
                    ->withAverageSpeed(MetersPerSecond::from(13.3))
                    ->withMinAverageSpeed(MetersPerSecond::from(20))
                    ->withMaxAverageSpeed(MetersPerSecond::from(20))
                    ->build(),
                0,
            ],
        ];
    }
}
