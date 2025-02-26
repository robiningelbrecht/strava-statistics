<?php

namespace App\Tests\Domain\Strava\Activity;

use App\Domain\Strava\Activity\Stream\PowerOutput;
use PHPUnit\Framework\TestCase;
use Spatie\Snapshots\MatchesSnapshots;

class ActivityEFtpTest extends TestCase
{
    use MatchesSnapshots;

    public function testNullEFTP(): void
    {
        $activity = ActivityBuilder::fromDefaults()->build();

        $this->assertNull($activity->getEFTP());
    }

    public function testEnrichEFTP(): void
    {
        $eftpValue = 200;
        $relativeEftpValue = 4.25;
        $time = '1 h';

        $eftp = PowerOutput::fromState(
            time: $time,
            power: $eftpValue,
            relativePower: $relativeEftpValue
        );
        $activity = ActivityBuilder::fromDefaults()->build();
        $activity->enrichWithEFTP($eftp);

        $this->assertEquals($activity->getEFTP()->getPower(), $eftpValue);
        $this->assertEquals($activity->getEFTP()->getRelativePower(), $relativeEftpValue);
        $this->assertEquals($activity->getEFTP()->getTime(), $time);
    }
}
