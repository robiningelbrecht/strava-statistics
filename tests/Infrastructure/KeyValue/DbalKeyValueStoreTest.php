<?php

namespace App\Tests\Infrastructure\KeyValue;

use App\Infrastructure\Exception\EntityNotFound;
use App\Infrastructure\KeyValue\DbalKeyValueStore;
use App\Infrastructure\KeyValue\Key;
use App\Infrastructure\KeyValue\KeyValue;
use App\Infrastructure\KeyValue\Value;
use App\Tests\ContainerTestCase;

class DbalKeyValueStoreTest extends ContainerTestCase
{
    private DbalKeyValueStore $keyValueStore;

    public function testFind(): void
    {
        $keyValue = KeyValue::fromState(
            key: Key::STRAVA_LIMITS_HAVE_BEEN_REACHED,
            value: Value::fromString('1989-08-14'),
        );
        $this->keyValueStore->save($keyValue);
        $this->keyValueStore->save($keyValue);

        $this->assertEquals(
            $keyValue,
            $this->keyValueStore->find(Key::STRAVA_LIMITS_HAVE_BEEN_REACHED)
        );
    }

    public function testClear(): void
    {
        $keyValue = KeyValue::fromState(
            key: Key::STRAVA_LIMITS_HAVE_BEEN_REACHED,
            value: Value::fromString('1989-08-14'),
        );
        $this->keyValueStore->save($keyValue);
        $this->keyValueStore->clear(Key::STRAVA_LIMITS_HAVE_BEEN_REACHED);

        $this->expectException(EntityNotFound::class);
        $this->keyValueStore->find(Key::STRAVA_LIMITS_HAVE_BEEN_REACHED);
    }

    public function testItShouldThrowWhenNotFound(): void
    {
        $this->expectException(EntityNotFound::class);
        $this->keyValueStore->find(Key::STRAVA_LIMITS_HAVE_BEEN_REACHED);
    }

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->keyValueStore = new DbalKeyValueStore(
            $this->getConnection()
        );
    }
}
