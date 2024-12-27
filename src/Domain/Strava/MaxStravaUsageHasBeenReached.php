<?php

declare(strict_types=1);

namespace App\Domain\Strava;

use App\Infrastructure\Exception\EntityNotFound;
use App\Infrastructure\KeyValue\Key;
use App\Infrastructure\KeyValue\KeyValue;
use App\Infrastructure\KeyValue\KeyValueStore;
use App\Infrastructure\KeyValue\Value;
use App\Infrastructure\Time\Clock\Clock;

readonly class MaxStravaUsageHasBeenReached
{
    public function __construct(
        private Clock $clock,
        private KeyValueStore $keyValueStore,
    ) {
    }

    public function clear(): void
    {
        $this->keyValueStore->clear(Key::STRAVA_LIMITS_HAVE_BEEN_REACHED);
    }

    public function markAsReached(): void
    {
        $this->keyValueStore->save(KeyValue::fromState(
            key: Key::STRAVA_LIMITS_HAVE_BEEN_REACHED,
            value: Value::fromString($this->clock->getCurrentDateTimeImmutable()->format('Y-m-d'))
        ));
    }

    public function hasReached(): bool
    {
        try {
            $value = $this->keyValueStore->find(Key::STRAVA_LIMITS_HAVE_BEEN_REACHED);
        } catch (EntityNotFound) {
            return false;
        }

        return (string) $value === $this->clock->getCurrentDateTimeImmutable()->format('Y-m-d');
    }
}
