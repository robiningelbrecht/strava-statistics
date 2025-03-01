<?php

namespace App\Tests\Domain\Strava\Activity;

use App\Domain\Strava\EFtp\EFtpOutput;
use PHPUnit\Framework\TestCase;
use Spatie\Snapshots\MatchesSnapshots;

class ActivityEFtpTest extends TestCase
{
    use MatchesSnapshots;

    public function testNullEFTP(): void
    {
        $activity = ActivityBuilder::fromDefaults()->build();

        $this->assertNull($activity->getEFtp());
    }

    public function testEnrichEFTP(): void
    {
        $eftpValue = 200;
        $relativeEftpValue = 4.25;
        $timeInSeconds = 3600;
        $time = '1 h';

        $eftp = EFtpOutput::fromState(
            power: $eftpValue,
            timeIntervalInSeconds: $timeInSeconds,
            formattedTimeInterval: $time,
            relativePower: $relativeEftpValue
        );
        $activity = ActivityBuilder::fromDefaults()->build();
        $activity->enrichWithEFtp($eftp);

        $this->assertEquals($activity->getEFtp()->getPower(), $eftpValue);
        $this->assertEquals($activity->getEFtp()->getRelativePower(), $relativeEftpValue);
        $this->assertEquals($activity->getEFtp()->getFormattedTimeInterval(), $time);
        $this->assertEquals($activity->getEFtp()->getTimeIntervalInSeconds(), $timeInSeconds);
    }
}
