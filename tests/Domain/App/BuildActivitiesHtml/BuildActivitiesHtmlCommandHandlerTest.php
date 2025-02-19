<?php

namespace App\Tests\Domain\App\BuildActivitiesHtml;

use App\Domain\App\BuildActivitiesHtml\BuildActivitiesHtml;
use App\Infrastructure\CQRS\Bus\CommandBus;
use App\Infrastructure\ValueObject\Time\SerializableDateTime;
use App\Tests\ContainerTestCase;
use App\Tests\Infrastructure\FileSystem\ProvideFileSystemWriteAssertion;
use App\Tests\ProvideTestData;
use League\Flysystem\FilesystemOperator;
use Spatie\Snapshots\MatchesSnapshots;

class BuildActivitiesHtmlCommandHandlerTest extends ContainerTestCase
{
    use MatchesSnapshots;
    use ProvideTestData;
    use ProvideFileSystemWriteAssertion;

    private CommandBus $commandBus;

    public function testHandle(): void
    {
        $this->provideFullTestSet();

        $this->commandBus->dispatch(new BuildActivitiesHtml(SerializableDateTime::fromString('2023-10-17 16:15:04')));

        /** @var \App\Tests\Infrastructure\FileSystem\SpyFileSystem $fileSystem */
        $fileSystem = $this->getContainer()->get(FilesystemOperator::class);
        $this->assertFileSystemWrites($fileSystem->getWrites());
    }

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->commandBus = $this->getContainer()->get(CommandBus::class);
    }
}
