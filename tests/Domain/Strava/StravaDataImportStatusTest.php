<?php

namespace App\Tests\Domain\Strava;

use App\Domain\Strava\StravaDataImportStatus;
use App\Infrastructure\KeyValue\KeyValueStore;
use App\Infrastructure\ValueObject\Time\SerializableDateTime;
use App\Tests\ContainerTestCase;
use App\Tests\Infrastructure\Time\Clock\PausedClock;

class StravaDataImportStatusTest extends ContainerTestCase
{
    private StravaDataImportStatus $stravaDataImportStatus;

    public function testIsCompleted(): void
    {
        $this->assertFalse($this->stravaDataImportStatus->isCompleted());
        $this->stravaDataImportStatus->markActivityImportAsCompleted();
        $this->assertFalse($this->stravaDataImportStatus->isCompleted());
        $this->stravaDataImportStatus->markGearImportAsCompleted();
        $this->assertTrue($this->stravaDataImportStatus->isCompleted());
    }

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->stravaDataImportStatus = new StravaDataImportStatus(
            $this->getContainer()->get(KeyValueStore::class),
            PausedClock::on(SerializableDateTime::fromString('2024-12-26'))
        );
    }
}
