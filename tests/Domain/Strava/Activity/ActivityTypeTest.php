<?php

namespace App\Tests\Domain\Strava\Activity;

use App\Domain\Strava\Activity\ActivityType;
use PHPUnit\Framework\TestCase;
use Spatie\Snapshots\MatchesSnapshots;

class ActivityTypeTest extends TestCase
{
    use MatchesSnapshots;

    public function testGetTemplateName(): void
    {
        $snapshot = [];
        foreach (ActivityType::cases() as $activityType) {
            $snapshot[] = $activityType->getTemplateName();
        }
        $this->assertMatchesJsonSnapshot($snapshot);
    }

    public function testGetSportTypes(): void
    {
        $snapshot = [];
        foreach (ActivityType::cases() as $activityType) {
            $snapshot[] = $activityType->getSportTypes();
        }
        $this->assertMatchesJsonSnapshot($snapshot);
    }
}
