<?php

namespace App\Tests\Domain\Strava\Athlete\Weight;

use App\Domain\Strava\Athlete\Weight\AthleteWeightRepository;
use App\Domain\Strava\Athlete\Weight\DbalAthleteWeightRepository;
use App\Infrastructure\Exception\EntityNotFound;
use App\Infrastructure\ValueObject\Time\SerializableDateTime;
use App\Tests\ContainerTestCase;

class DbalAthleteWeightRepositoryTest extends ContainerTestCase
{
    private AthleteWeightRepository $athleteWeightRepository;

    public function testRemoveAll(): void
    {
        $weightOne = AthleteWeightBuilder::fromDefaults()
            ->withOn(SerializableDateTime::fromString('2023-04-01'))
            ->withWeightInGrams(74000)
            ->build();
        $this->athleteWeightRepository->save($weightOne);
        $WeightTwo = AthleteWeightBuilder::fromDefaults()
            ->withOn(SerializableDateTime::fromString('2023-05-25'))
            ->withWeightInGrams(75000)
            ->build();
        $this->athleteWeightRepository->save($WeightTwo);
        $weightThree = AthleteWeightBuilder::fromDefaults()
            ->withOn(SerializableDateTime::fromString('2023-08-01'))
            ->withWeightInGrams(70000)
            ->build();
        $this->athleteWeightRepository->save($weightThree);
        $weightFour = AthleteWeightBuilder::fromDefaults()
            ->withOn(SerializableDateTime::fromString('2023-09-24'))
            ->withWeightInGrams(60000)
            ->build();
        $this->athleteWeightRepository->save($weightFour);

        $this->assertNotEmpty($this->getConnection()->executeQuery('SELECT * FROM AthleteWeight')->fetchAllAssociative());
        $this->athleteWeightRepository->removeAll();
        $this->assertEmpty($this->getConnection()->executeQuery('SELECT * FROM AthleteWeight')->fetchAllAssociative());
    }

    public function testFindForDate(): void
    {
        $weightOne = AthleteWeightBuilder::fromDefaults()
            ->withOn(SerializableDateTime::fromString('2023-04-01'))
            ->withWeightInGrams(74000)
            ->build();
        $this->athleteWeightRepository->save($weightOne);
        $WeightTwo = AthleteWeightBuilder::fromDefaults()
            ->withOn(SerializableDateTime::fromString('2023-05-25'))
            ->withWeightInGrams(75000)
            ->build();
        $this->athleteWeightRepository->save($WeightTwo);
        $weightThree = AthleteWeightBuilder::fromDefaults()
            ->withOn(SerializableDateTime::fromString('2023-08-01'))
            ->withWeightInGrams(70000)
            ->build();
        $this->athleteWeightRepository->save($weightThree);
        $weightFour = AthleteWeightBuilder::fromDefaults()
            ->withOn(SerializableDateTime::fromString('2023-09-24'))
            ->withWeightInGrams(60000)
            ->build();
        $this->athleteWeightRepository->save($weightFour);

        $this->assertEquals(
            $weightOne,
            $this->athleteWeightRepository->find(SerializableDateTime::fromString('2023-05-24'))
        );
        $this->assertEquals(
            $WeightTwo,
            $this->athleteWeightRepository->find(SerializableDateTime::fromString('2023-05-25'))
        );
        $this->assertEquals(
            $WeightTwo,
            $this->athleteWeightRepository->find(SerializableDateTime::fromString('2023-06-25'))
        );
        $this->assertEquals(
            $weightThree,
            $this->athleteWeightRepository->find(SerializableDateTime::fromString('2023-08-04'))
        );
        $this->assertEquals(
            $weightFour,
            $this->athleteWeightRepository->find(SerializableDateTime::fromString('2023-09-24'))
        );
        $this->assertEquals(
            $weightFour,
            $this->athleteWeightRepository->find(SerializableDateTime::fromString('2023-10-24'))
        );
    }

    public function testItShouldThrowWhenNotFound(): void
    {
        $weightOne = AthleteWeightBuilder::fromDefaults()
            ->withOn(SerializableDateTime::fromString('2023-04-01'))
            ->withWeightInGrams(60000)
            ->build();
        $this->athleteWeightRepository->save($weightOne);
        $weightTwo = AthleteWeightBuilder::fromDefaults()
            ->withOn(SerializableDateTime::fromString('2023-05-25'))
            ->withWeightInGrams(65000)
            ->build();
        $this->athleteWeightRepository->save($weightTwo);
        $weightThree = AthleteWeightBuilder::fromDefaults()
            ->withOn(SerializableDateTime::fromString('2023-08-01'))
            ->withWeightInGrams(70000)
            ->build();
        $this->athleteWeightRepository->save($weightThree);
        $weightFour = AthleteWeightBuilder::fromDefaults()
            ->withOn(SerializableDateTime::fromString('2023-09-24'))
            ->withWeightInGrams(75000)
            ->build();
        $this->athleteWeightRepository->save($weightFour);

        $this->expectException(EntityNotFound::class);

        $this->athleteWeightRepository->find(SerializableDateTime::fromString('2023-01-01'));
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->athleteWeightRepository = new DbalAthleteWeightRepository(
            $this->getConnection()
        );
    }
}
