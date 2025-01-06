<?php

declare(strict_types=1);

namespace App\Domain\Strava;

use App\Infrastructure\Exception\EntityNotFound;
use App\Infrastructure\KeyValue\Key;
use App\Infrastructure\KeyValue\KeyValue;
use App\Infrastructure\KeyValue\KeyValueStore;
use App\Infrastructure\KeyValue\Value;
use App\Infrastructure\Time\Clock\Clock;

final readonly class StravaDataImportStatus
{
    public function __construct(
        private KeyValueStore $keyValueStore,
        private Clock $clock,
    ) {
    }

    public function markActivityImportAsCompleted(): void
    {
        $this->keyValueStore->save(KeyValue::fromState(
            key: Key::STRAVA_ACTIVITY_IMPORT,
            value: Value::fromString($this->clock->getCurrentDateTimeImmutable()->format('Y-m-d')),
        ));
    }

    public function markActivityImportAsUncompleted(): void
    {
        $this->keyValueStore->clear(Key::STRAVA_ACTIVITY_IMPORT);
    }

    public function markGearImportAsCompleted(): void
    {
        $this->keyValueStore->save(KeyValue::fromState(
            key: Key::STRAVA_GEAR_IMPORT,
            value: Value::fromString($this->clock->getCurrentDateTimeImmutable()->format('Y-m-d')),
        ));
    }

    public function markGearImportAsUncompleted(): void
    {
        $this->keyValueStore->clear(Key::STRAVA_GEAR_IMPORT);
    }

    public function isCompleted(): bool
    {
        try {
            $this->keyValueStore->find(Key::STRAVA_ACTIVITY_IMPORT);
        } catch (EntityNotFound) {
            return false;
        }

        try {
            $this->keyValueStore->find(Key::STRAVA_GEAR_IMPORT);
        } catch (EntityNotFound) {
            return false;
        }

        return true;
    }
}
