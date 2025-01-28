<?php

namespace App\Tests\Domain\Manifest\BuildManifest;

use App\Domain\Manifest\BuildManifest\BuildManifest;
use App\Domain\Strava\Athlete\Athlete;
use App\Domain\Strava\Athlete\AthleteRepository;
use App\Infrastructure\CQRS\Bus\CommandBus;
use App\Infrastructure\Serialization\Json;
use App\Tests\ContainerTestCase;
use App\Tests\ProvideTestData;
use League\Flysystem\FilesystemOperator;
use Spatie\Snapshots\MatchesSnapshots;

class BuildManifestCommandHandlerTest extends ContainerTestCase
{
    use MatchesSnapshots;
    use ProvideTestData;

    private CommandBus $commandBus;

    public function testHandle(): void
    {
        /** @var AthleteRepository $athleteRepository */
        $athleteRepository = $this->getContainer()->get(AthleteRepository::class);
        $athleteRepository->save(Athlete::create([
            'id' => 100,
            'birthDate' => '1989-08-14',
            'firstname' => 'Robin',
            'lastname' => 'Ingelbrecht',
        ]));

        $this->commandBus->dispatch(new BuildManifest());

        /** @var \App\Tests\Infrastructure\FileSystem\SpyFileSystem $fileSystem */
        $fileSystem = $this->getContainer()->get(FilesystemOperator::class);
        $this->assertMatchesJsonSnapshot(Json::encode($fileSystem->getWrites()));
    }

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->commandBus = $this->getContainer()->get(CommandBus::class);
    }
}
