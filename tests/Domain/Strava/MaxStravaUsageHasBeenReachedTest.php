<?php

namespace App\Tests\Domain\Strava;

use App\Domain\Strava\MaxStravaUsageHasBeenReached;
use App\Infrastructure\KeyValue\KeyValueStore;
use App\Infrastructure\ValueObject\Time\SerializableDateTime;
use App\Tests\ContainerTestCase;
use App\Tests\Infrastructure\Time\Clock\PausedClock;

class MaxStravaUsageHasBeenReachedTest extends ContainerTestCase
{
    private MaxStravaUsageHasBeenReached $maxStravaUsageHasBeenReached;

    public function testClear(): void
    {
        $this->maxStravaUsageHasBeenReached->markAsReached();

        $this->assertTrue(
            $this->maxStravaUsageHasBeenReached->hasReached()
        );
        $this->maxStravaUsageHasBeenReached->clear();
        $this->assertFalse(
            $this->maxStravaUsageHasBeenReached->hasReached()
        );
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->maxStravaUsageHasBeenReached = new MaxStravaUsageHasBeenReached(
            PausedClock::on(SerializableDateTime::fromString('2024-12-26')),
            $this->getContainer()->get(KeyValueStore::class)
        );
    }
}
