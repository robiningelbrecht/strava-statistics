<?php

namespace App\Domain\Strava\Gear;

interface GearRepository
{
    public function save(Gear $gear): void;

    public function findAll(): Gears;

    public function find(GearId $gearId): Gear;
}
