<?php

namespace App\Tests\Domain\Strava\Activity;

use App\Domain\Strava\Activity\ActivitiesToSkipDuringImport;
use App\Domain\Strava\Activity\ActivityId;
use PHPUnit\Framework\TestCase;

class ActivitiesToSkipDuringImportTest extends TestCase
{
    public function testFrom(): void
    {
        $this->assertEquals(
            ActivitiesToSkipDuringImport::empty(),
            ActivitiesToSkipDuringImport::from([])
        );

        $this->assertEquals(
            ActivitiesToSkipDuringImport::empty()->add(ActivityId::fromUnprefixed('test')),
            ActivitiesToSkipDuringImport::from(['test'])
        );
    }
}
