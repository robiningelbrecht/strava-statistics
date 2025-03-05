<?php

namespace App\Tests\Domain\Strava\Activity\ImportActivities;

use App\Domain\Strava\Activity\ActivityVisibility;
use App\Domain\Strava\Activity\ImportActivities\ActivityVisibilitiesToImport;
use PHPUnit\Framework\TestCase;

class ActivityVisibilitiesToImportTest extends TestCase
{
    public function testFrom(): void
    {
        $this->assertEquals(
            ActivityVisibilitiesToImport::fromArray(ActivityVisibility::cases()),
            ActivityVisibilitiesToImport::from([])
        );

        $this->assertEquals(
            ActivityVisibilitiesToImport::fromArray([ActivityVisibility::ONLY_ME, ActivityVisibility::EVERYONE]),
            ActivityVisibilitiesToImport::from(['only_me', 'everyone'])
        );
    }
}
