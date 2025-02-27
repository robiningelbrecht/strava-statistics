<?php

declare(strict_types=1);

namespace App\Tests\Domain\App;

use App\Infrastructure\CQRS\Bus\CommandBus;
use App\Infrastructure\CQRS\DomainCommand;
use App\Tests\ContainerTestCase;
use App\Tests\ProvideTestData;
use League\Flysystem\FilesystemOperator;
use Spatie\Snapshots\MatchesSnapshots;

abstract class BuildAppFilesTestCase extends ContainerTestCase
{
    use ProvideTestData;
    use MatchesSnapshots;

    private string $snapshotName;

    abstract protected function getDomainCommand(): DomainCommand;

    protected CommandBus $commandBus;

    public function testHandle(): void
    {
        $this->provideFullTestSet();

        $this->commandBus->dispatch($this->getDomainCommand());

        /** @var \App\Tests\Infrastructure\FileSystem\SpyFileSystem $fileSystem */
        $fileSystem = $this->getContainer()->get(FilesystemOperator::class);
        $this->assertFileSystemWrites($fileSystem->getWrites());
    }

    protected function assertFileSystemWrites(array $writes): void
    {
        foreach ($writes as $location => $content) {
            $this->snapshotName = preg_replace('/[^a-zA-Z0-9]/', '-', $location);
            if (str_ends_with($location, '.json')) {
                $this->assertMatchesJsonSnapshot($content);
                continue;
            }
            if (str_ends_with($location, '.html')) {
                $this->assertMatchesHtmlSnapshot($content);
                continue;
            }
            if (str_ends_with($location, '.gpx')) {
                $this->assertMatchesXmlSnapshot($content);
                continue;
            }
            $this->assertMatchesTextSnapshot($content);
        }
    }

    protected function getSnapshotId(): string
    {
        return new \ReflectionClass($this)->getShortName().'--'.
            $this->name().'--'.
            $this->snapshotName;
    }

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->commandBus = $this->getContainer()->get(CommandBus::class);
    }
}
