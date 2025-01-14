<?php

namespace App\Tests\Domain\Strava\Gear\ImportGear;

use App\Domain\Strava\Activity\ActivityId;
use App\Domain\Strava\Activity\WriteModel\ActivityRepository;
use App\Domain\Strava\Gear\GearId;
use App\Domain\Strava\Gear\GearRepository;
use App\Domain\Strava\Gear\ImportGear\ImportGear;
use App\Domain\Strava\Strava;
use App\Infrastructure\CQRS\Bus\CommandBus;
use App\Tests\ContainerTestCase;
use App\Tests\Domain\Strava\Activity\ActivityBuilder;
use App\Tests\Domain\Strava\Gear\GearBuilder;
use App\Tests\Domain\Strava\SpyStrava;
use App\Tests\SpyOutput;
use League\Flysystem\FilesystemOperator;
use Spatie\Snapshots\MatchesSnapshots;

class ImportGearCommandHandlerTest extends ContainerTestCase
{
    use MatchesSnapshots;

    private CommandBus $commandBus;
    private SpyStrava $strava;

    public function testHandleWithTooManyRequests(): void
    {
        $output = new SpyOutput();
        $this->strava->setMaxNumberOfCallsBeforeTriggering429(3);

        $this->getContainer()->get(GearRepository::class)->add(
            GearBuilder::fromDefaults()
                ->withGearId(GearId::fromUnprefixed('b12659861'))
                ->build()
        );
        $this->getContainer()->get(ActivityRepository::class)->add(
            ActivityBuilder::fromDefaults()
                ->withActivityId(ActivityId::fromUnprefixed(1))
                ->withGearId(GearId::fromUnprefixed('b12659861'))
                ->build()
        );
        $this->getContainer()->get(ActivityRepository::class)->add(
            ActivityBuilder::fromDefaults()
                ->withActivityId(ActivityId::fromUnprefixed(2))
                ->withGearId(GearId::fromUnprefixed('b12659743'))
                ->build()
        );
        $this->getContainer()->get(ActivityRepository::class)->add(
            ActivityBuilder::fromDefaults()
                ->withActivityId(ActivityId::fromUnprefixed(3))
                ->withGearId(GearId::fromUnprefixed('b12659792'))
                ->build()
        );

        $this->commandBus->dispatch(new ImportGear($output));

        $this->assertMatchesTextSnapshot($output);

        $this->assertEmpty(
            $this->getConnection()->executeQuery('SELECT * FROM KeyValue')->fetchAllAssociative()
        );

        /** @var \App\Tests\Infrastructure\FileSystem\SpyFileSystem $fileSystem */
        $fileSystem = $this->getContainer()->get(FilesystemOperator::class);
        $this->assertEmpty($fileSystem->getWrites());
    }

    public function testHandle(): void
    {
        $output = new SpyOutput();
        $this->strava->setMaxNumberOfCallsBeforeTriggering429(10000);

        $this->getContainer()->get(GearRepository::class)->add(
            GearBuilder::fromDefaults()
                ->withGearId(GearId::fromUnprefixed('b12659861'))
                ->build()
        );
        $this->getContainer()->get(ActivityRepository::class)->add(
            ActivityBuilder::fromDefaults()
                ->withActivityId(ActivityId::fromUnprefixed(1))
                ->withGearId(GearId::fromUnprefixed('b12659861'))
                ->build()
        );
        $this->getContainer()->get(ActivityRepository::class)->add(
            ActivityBuilder::fromDefaults()
                ->withActivityId(ActivityId::fromUnprefixed(2))
                ->withGearId(GearId::fromUnprefixed('b12659743'))
                ->build()
        );
        $this->getContainer()->get(ActivityRepository::class)->add(
            ActivityBuilder::fromDefaults()
                ->withActivityId(ActivityId::fromUnprefixed(3))
                ->withGearId(GearId::fromUnprefixed('b12659792'))
                ->build()
        );

        $this->commandBus->dispatch(new ImportGear($output));

        $this->assertMatchesTextSnapshot($output);

        $this->assertMatchesJsonSnapshot(
            $this->getConnection()->executeQuery('SELECT * FROM KeyValue')->fetchAllAssociative()
        );

        /** @var \App\Tests\Infrastructure\FileSystem\SpyFileSystem $fileSystem */
        $fileSystem = $this->getContainer()->get(FilesystemOperator::class);
        $this->assertEmpty($fileSystem->getWrites());
    }

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->commandBus = $this->getContainer()->get(CommandBus::class);
        $this->strava = $this->getContainer()->get(Strava::class);
    }
}
