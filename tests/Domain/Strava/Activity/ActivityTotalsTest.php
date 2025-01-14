<?php

namespace App\Tests\Domain\Strava\Activity;

use App\Domain\Strava\Activity\ActivityTotals;
use App\Domain\Strava\Activity\ReadModel\Activities;
use App\Infrastructure\ValueObject\Time\SerializableDateTime;
use App\Tests\Domain\Strava\Activity\ReadModel\ActivityDetailsBuilder;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Spatie\Snapshots\MatchesSnapshots;

class ActivityTotalsTest extends TestCase
{
    use MatchesSnapshots;

    #[DataProvider(methodName: 'provideActivityTotals')]
    public function testGetTotalDaysSinceFirstActivity(ActivityTotals $activityTotals): void
    {
        $this->assertMatchesTextSnapshot($activityTotals->getTotalDaysSinceFirstActivity());
    }

    public static function provideActivityTotals(): array
    {
        return [
            [
                ActivityTotals::fromActivities(
                    activities: Activities::fromArray([
                        ActivityDetailsBuilder::fromDefaults()
                            ->withStartDateTime(SerializableDateTime::fromString('2023-11-24'))
                            ->build(),
                    ]),
                    now: SerializableDateTime::fromString('2023-11-25'),
                ),
            ],
            [
                ActivityTotals::fromActivities(
                    activities: Activities::fromArray([
                        ActivityDetailsBuilder::fromDefaults()
                            ->withStartDateTime(SerializableDateTime::fromString('2023-11-01'))
                            ->build(),
                    ]),
                    now: SerializableDateTime::fromString('2023-11-25'),
                ),
            ],
            [
                ActivityTotals::fromActivities(
                    activities: Activities::fromArray([
                        ActivityDetailsBuilder::fromDefaults()
                            ->withStartDateTime(SerializableDateTime::fromString('2023-04-24'))
                            ->build(),
                    ]),
                    now: SerializableDateTime::fromString('2023-11-25'),
                ),
            ],  [
                ActivityTotals::fromActivities(
                    activities: Activities::fromArray([
                        ActivityDetailsBuilder::fromDefaults()
                            ->withStartDateTime(SerializableDateTime::fromString('2022-11-24'))
                            ->build(),
                    ]),
                    now: SerializableDateTime::fromString('2023-11-25'),
                ),
            ],
            [
                ActivityTotals::fromActivities(
                    activities: Activities::fromArray([
                        ActivityDetailsBuilder::fromDefaults()
                            ->withStartDateTime(SerializableDateTime::fromString('2017-11-24'))
                            ->build(),
                    ]),
                    now: SerializableDateTime::fromString('2023-11-25'),
                ),
            ],
        ];
    }
}
