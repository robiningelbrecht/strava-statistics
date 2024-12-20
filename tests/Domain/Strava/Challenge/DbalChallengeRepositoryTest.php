<?php

namespace App\Tests\Domain\Strava\Challenge;

use App\Domain\Strava\Challenge\ChallengeId;
use App\Domain\Strava\Challenge\ChallengeRepository;
use App\Domain\Strava\Challenge\Challenges;
use App\Domain\Strava\Challenge\DbalChallengeRepository;
use App\Infrastructure\Exception\EntityNotFound;
use App\Infrastructure\ValueObject\Time\SerializableDateTime;
use App\Tests\ContainerTestCase;

class DbalChallengeRepositoryTest extends ContainerTestCase
{
    private ChallengeRepository $challengeRepository;

    public function testFindAndSave(): void
    {
        $challenge = ChallengeBuilder::fromDefaults()->build();
        $this->challengeRepository->add($challenge);

        $this->assertEquals(
            $challenge,
            $this->challengeRepository->find($challenge->getId())
        );
    }

    public function testItShouldThrowWhenNotFound(): void
    {
        $this->expectException(EntityNotFound::class);
        $this->challengeRepository->find(ChallengeId::fromUnprefixed('1'));
    }

    public function testFindAll(): void
    {
        $challengeOne = ChallengeBuilder::fromDefaults()
            ->withChallengeId(ChallengeId::fromUnprefixed('1'))
            ->withCreatedOn(SerializableDateTime::fromString('2023-10-10 14:00:34'))
            ->build();
        $this->challengeRepository->add($challengeOne);
        $challengeTwo = ChallengeBuilder::fromDefaults()
            ->withChallengeId(ChallengeId::fromUnprefixed('2'))
            ->withCreatedOn(SerializableDateTime::fromString('2023-10-10 15:00:34'))
            ->build();
        $this->challengeRepository->add($challengeTwo);

        $this->assertEquals(
            Challenges::fromArray([$challengeTwo, $challengeOne]),
            $this->challengeRepository->findAll()
        );
    }

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->challengeRepository = new DbalChallengeRepository(
            $this->getConnection()
        );
    }
}
