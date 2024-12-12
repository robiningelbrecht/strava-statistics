<?php

namespace App\Tests\Domain\Strava\Gear;

use App\Domain\Strava\Gear\DbalGearRepository;
use App\Domain\Strava\Gear\GearId;
use App\Domain\Strava\Gear\GearRepository;
use App\Domain\Strava\Gear\Gears;
use App\Infrastructure\Exception\EntityNotFound;
use App\Tests\ContainerTestCase;

class DbalGearRepositoryTest extends ContainerTestCase
{
    private GearRepository $gearRepository;

    public function testFindAndSave(): void
    {
        $gear = GearBuilder::fromDefaults()
            ->withGearId(GearId::fromUnprefixed(1))
            ->withDistanceInMeter(1230)
            ->build();
        $this->gearRepository->add($gear);

        $this->assertEquals(
            $gear,
            $this->gearRepository->find($gear->getId())
        );
    }

    public function testItShouldThrowWhenNotFound(): void
    {
        $this->expectException(EntityNotFound::class);
        $this->gearRepository->find(GearId::fromUnprefixed('1'));
    }

    public function testFindAll(): void
    {
        $gearOne = GearBuilder::fromDefaults()
            ->withGearId(GearId::fromUnprefixed(1))
            ->withDistanceInMeter(1230)
            ->build();
        $this->gearRepository->add($gearOne);
        $gearTwo = GearBuilder::fromDefaults()
            ->withGearId(GearId::fromUnprefixed(2))
            ->withDistanceInMeter(10230)
            ->build();
        $this->gearRepository->add($gearTwo);
        $gearThree = GearBuilder::fromDefaults()
            ->withGearId(GearId::fromUnprefixed(3))
            ->withDistanceInMeter(230)
            ->build();
        $this->gearRepository->add($gearThree);

        $this->assertEquals(
            Gears::fromArray([$gearTwo, $gearOne, $gearThree]),
            $this->gearRepository->findAll()
        );
    }

    public function testUpdate(): void
    {
        $gear = GearBuilder::fromDefaults()
            ->withGearId(GearId::fromUnprefixed(1))
            ->withDistanceInMeter(1000)
            ->build();
        $this->gearRepository->add($gear);

        $this->assertEquals(
            1000,
            $gear->getDistanceInMeter()
        );

        $gear->updateDistance(30000, 30.00);
        $this->gearRepository->update($gear);

        $this->assertEquals(
            30000,
            $this->gearRepository->find(GearId::fromUnprefixed(1))->getDistanceInMeter()
        );
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->gearRepository = new DbalGearRepository(
            $this->getConnection()
        );
    }
}