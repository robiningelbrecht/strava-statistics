<?php

namespace App\Tests\Infrastructure\Serialization;

use App\Infrastructure\Serialization\Json;
use PHPUnit\Framework\TestCase;
use Spatie\Snapshots\MatchesSnapshots;

class JsonTest extends TestCase
{
    use MatchesSnapshots;

    public function testEncodeDecode(): void
    {
        $array = ['random' => ['array' => ['with', 'children']]];

        $encoded = Json::encode($array);
        $this->assertMatchesJsonSnapshot($encoded);

        $this->assertEquals($array, Json::decode($encoded));
        $this->assertEquals($array, Json::encodeAndDecode($array));
    }
}
