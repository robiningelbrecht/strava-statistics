<?php

declare(strict_types=1);

namespace App\Tests\Domain\App;

use App\Infrastructure\CQRS\Bus\CommandBus;
use App\Infrastructure\CQRS\Bus\DomainCommand;
use App\Tests\ContainerTestCase;
use App\Tests\ProvideTestData;
use League\Flysystem\FilesystemOperator;
use Spatie\Snapshots\MatchesSnapshots;

abstract class BuildAppFilesTestCase extends ContainerTestCase
{
    use ProvideTestData;
    use MatchesSnapshots;

    abstract protected function getDomainCommand(): DomainCommand;

    private CommandBus $commandBus;

    public function testHandle(): void
    {
        $this->provideFullTestSet();

        $this->commandBus->dispatch($this->getDomainCommand());

        /** @var \App\Tests\Infrastructure\FileSystem\SpyFileSystem $fileSystem */
        $fileSystem = $this->getContainer()->get(FilesystemOperator::class);
        $this->assertFileSystemWrites($fileSystem->getWrites());
    }

    public function assertFileSystemWrites(array $writes): void
    {
        foreach ($writes as $location => $content) {
            if (str_ends_with($location, '.json')) {
                $this->assertMatchesJsonSnapshot($content);
                continue;
            }
            if (str_ends_with($location, '.html')) {
                $this->assertMatchesHtmlSnapshot($content);
                continue;
            }
            $this->assertMatchesTextSnapshot($content);
        }
    }

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->commandBus = $this->getContainer()->get(CommandBus::class);
    }
}
