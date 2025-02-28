<?php

declare(strict_types=1);

namespace App\Tests\Domain\App;

use App\Infrastructure\CQRS\Bus\CommandBus;
use App\Infrastructure\CQRS\DomainCommand;
use App\Tests\ContainerTestCase;
use App\Tests\ProvideTestData;
use League\Flysystem\FileAttributes;
use League\Flysystem\FilesystemOperator;
use Spatie\Snapshots\MatchesSnapshots;

abstract class BuildAppFilesTestCase extends ContainerTestCase
{
    use ProvideTestData;
    use MatchesSnapshots;

    private string $snapshotName;

    /**
     * @return FilesystemOperator[]
     */
    protected function getFileSystemOperators(): array
    {
        return [$this->getContainer()->get('build.storage')];
    }

    abstract protected function getDomainCommand(): DomainCommand;

    protected CommandBus $commandBus;

    public function testHandle(): void
    {
        $this->provideFullTestSet();

        $this->commandBus->dispatch($this->getDomainCommand());

        foreach ($this->getFileSystemOperators() as $fileSystemOperator) {
            $this->assertFileSystemWrites($fileSystemOperator);
        }
    }

    protected function assertFileSystemWrites(FilesystemOperator $fileSystem): void
    {
        foreach ($fileSystem->listContents('/', true) as $item) {
            $path = $item->path();

            if (!$item instanceof FileAttributes) {
                continue;
            }

            $this->snapshotName = preg_replace('/[^a-zA-Z0-9]/', '-', $path);
            $content = $fileSystem->read($path);
            if (str_ends_with($path, '.json')) {
                $this->assertMatchesJsonSnapshot($content);
                continue;
            }
            if (str_ends_with($path, '.html')) {
                $this->assertMatchesHtmlSnapshot($content);
                continue;
            }
            if (str_ends_with($path, '.gpx') || str_ends_with($path, '.svg')) {
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
