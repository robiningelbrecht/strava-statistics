<?php

namespace App\Tests\Domain\Strava\Athlete\Weight\ImportAthleteWeight;

use App\Domain\Measurement\UnitSystem;
use App\Domain\Strava\Athlete\Weight\ImportAthleteWeight\AthleteWeightsFromEnvFile;
use PHPUnit\Framework\TestCase;

class AthleteWeightsFromEnvFileTest extends TestCase
{
    public function testItShouldThrow(): void
    {
        $this->expectExceptionObject(new \InvalidArgumentException('Invalid ATHLETE_WEIGHTS detected in .env file. Make sure the string is valid JSON'));
        AthleteWeightsFromEnvFile::fromString('{"lol}', UnitSystem::METRIC);
    }
}
