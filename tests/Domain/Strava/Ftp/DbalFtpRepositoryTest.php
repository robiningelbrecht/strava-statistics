<?php

namespace App\Tests\Domain\Strava\Ftp;

use App\Domain\Strava\Ftp\DbalFtpRepository;
use App\Domain\Strava\Ftp\FtpRepository;
use App\Domain\Strava\Ftp\Ftps;
use App\Domain\Strava\Ftp\FtpValue;
use App\Infrastructure\Exception\EntityNotFound;
use App\Infrastructure\ValueObject\Time\SerializableDateTime;
use App\Tests\ContainerTestCase;

class DbalFtpRepositoryTest extends ContainerTestCase
{
    private FtpRepository $ftpRepository;

    public function testFindForDate(): void
    {
        $ftpOne = FtpBuilder::fromDefaults()
            ->withSetOn(SerializableDateTime::fromString('2023-04-01'))
            ->withFtp(FtpValue::fromInt(198))
            ->build();
        $this->ftpRepository->save($ftpOne);
        $ftpTwo = FtpBuilder::fromDefaults()
            ->withSetOn(SerializableDateTime::fromString('2023-05-25'))
            ->withFtp(FtpValue::fromInt(220))
            ->build();
        $this->ftpRepository->save($ftpTwo);
        $ftpThree = FtpBuilder::fromDefaults()
            ->withSetOn(SerializableDateTime::fromString('2023-08-01'))
            ->withFtp(FtpValue::fromInt(238))
            ->build();
        $this->ftpRepository->save($ftpThree);
        $ftpFour = FtpBuilder::fromDefaults()
            ->withSetOn(SerializableDateTime::fromString('2023-09-24'))
            ->withFtp(FtpValue::fromInt(250))
            ->build();
        $this->ftpRepository->save($ftpFour);

        $this->assertEquals(
            $ftpOne,
            $this->ftpRepository->find(SerializableDateTime::fromString('2023-05-24'))
        );
        $this->assertEquals(
            $ftpTwo,
            $this->ftpRepository->find(SerializableDateTime::fromString('2023-05-25'))
        );
        $this->assertEquals(
            $ftpTwo,
            $this->ftpRepository->find(SerializableDateTime::fromString('2023-06-25'))
        );
        $this->assertEquals(
            $ftpThree,
            $this->ftpRepository->find(SerializableDateTime::fromString('2023-08-04'))
        );
        $this->assertEquals(
            $ftpFour,
            $this->ftpRepository->find(SerializableDateTime::fromString('2023-09-24'))
        );
        $this->assertEquals(
            $ftpFour,
            $this->ftpRepository->find(SerializableDateTime::fromString('2023-10-24'))
        );
    }

    public function testRemoveAll(): void
    {
        $ftpOne = FtpBuilder::fromDefaults()
            ->withSetOn(SerializableDateTime::fromString('2023-04-01'))
            ->withFtp(FtpValue::fromInt(198))
            ->build();
        $this->ftpRepository->save($ftpOne);
        $ftpTwo = FtpBuilder::fromDefaults()
            ->withSetOn(SerializableDateTime::fromString('2023-05-25'))
            ->withFtp(FtpValue::fromInt(220))
            ->build();
        $this->ftpRepository->save($ftpTwo);
        $ftpThree = FtpBuilder::fromDefaults()
            ->withSetOn(SerializableDateTime::fromString('2023-08-01'))
            ->withFtp(FtpValue::fromInt(238))
            ->build();
        $this->ftpRepository->save($ftpThree);
        $ftpFour = FtpBuilder::fromDefaults()
            ->withSetOn(SerializableDateTime::fromString('2023-09-24'))
            ->withFtp(FtpValue::fromInt(250))
            ->build();
        $this->ftpRepository->save($ftpFour);

        $this->assertNotEmpty($this->ftpRepository->findAll());
        $this->ftpRepository->removeAll();
        $this->assertEmpty($this->ftpRepository->findAll());
    }

    public function testItShouldThrowWhenNotFound(): void
    {
        $ftpOne = FtpBuilder::fromDefaults()
            ->withSetOn(SerializableDateTime::fromString('2023-04-01'))
            ->withFtp(FtpValue::fromInt(198))
            ->build();
        $this->ftpRepository->save($ftpOne);
        $ftpTwo = FtpBuilder::fromDefaults()
            ->withSetOn(SerializableDateTime::fromString('2023-05-25'))
            ->withFtp(FtpValue::fromInt(220))
            ->build();
        $this->ftpRepository->save($ftpTwo);
        $ftpThree = FtpBuilder::fromDefaults()
            ->withSetOn(SerializableDateTime::fromString('2023-08-01'))
            ->withFtp(FtpValue::fromInt(238))
            ->build();
        $this->ftpRepository->save($ftpThree);
        $ftpFour = FtpBuilder::fromDefaults()
            ->withSetOn(SerializableDateTime::fromString('2023-09-24'))
            ->withFtp(FtpValue::fromInt(250))
            ->build();
        $this->ftpRepository->save($ftpFour);

        $this->expectException(EntityNotFound::class);

        $this->ftpRepository->find(SerializableDateTime::fromString('2023-01-01'));
    }

    public function testFindAll(): void
    {
        $ftpOne = FtpBuilder::fromDefaults()
            ->withSetOn(SerializableDateTime::fromString('2023-04-01'))
            ->withFtp(FtpValue::fromInt(198))
            ->build();
        $this->ftpRepository->save($ftpOne);
        $ftpTwo = FtpBuilder::fromDefaults()
            ->withSetOn(SerializableDateTime::fromString('2023-05-25'))
            ->withFtp(FtpValue::fromInt(220))
            ->build();
        $this->ftpRepository->save($ftpTwo);
        $ftpThree = FtpBuilder::fromDefaults()
            ->withSetOn(SerializableDateTime::fromString('2023-08-01'))
            ->withFtp(FtpValue::fromInt(238))
            ->build();
        $this->ftpRepository->save($ftpThree);
        $ftpFour = FtpBuilder::fromDefaults()
            ->withSetOn(SerializableDateTime::fromString('2023-09-24'))
            ->withFtp(FtpValue::fromInt(250))
            ->build();
        $this->ftpRepository->save($ftpFour);

        $this->assertEquals(
            Ftps::fromArray([$ftpOne, $ftpTwo, $ftpThree, $ftpFour]),
            $this->ftpRepository->findAll()
        );
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->ftpRepository = new DbalFtpRepository(
            $this->getConnection()
        );
    }
}
