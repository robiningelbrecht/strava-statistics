<?php

namespace App\Tests\Domain\App\BuildApp;

use App\Domain\App\BuildApp\BuildApp;
use App\Infrastructure\CQRS\Bus\CommandBus;
use App\Infrastructure\ValueObject\Time\SerializableDateTime;
use App\Tests\ContainerTestCase;
use App\Tests\ProvideTestData;
use App\Tests\SpyOutput;
use League\Flysystem\FilesystemOperator;
use Spatie\Snapshots\MatchesSnapshots;

class BuildAppCommandHandlerTest extends ContainerTestCase
{
    use MatchesSnapshots;
    use ProvideTestData;

    private CommandBus $commandBus;
    private string $snapshotName;

    public function testHandle(): void
    {
        $this->provideFullTestSet();

        $output = new SpyOutput();
        $this->commandBus->dispatch(new BuildApp(
            output: $output,
            now: SerializableDateTime::fromString('2023-10-17 16:15:04')
        ));

        /** @var \App\Tests\Infrastructure\FileSystem\SpyFileSystem $fileSystem */
        $fileSystem = $this->getContainer()->get(FilesystemOperator::class);
        foreach ($fileSystem->getWrites() as $location => $content) {
            $this->snapshotName = $location;
            if (str_ends_with($location, '.json')) {
                $this->assertMatchesJsonSnapshot($content);
                continue;
            }
            $this->assertMatchesHtmlSnapshot($content);
        }
        $this->snapshotName = 'consoleOutput';
        $this->assertMatchesTextSnapshot($output);
    }

    public function testHandleForRunningActivitiesOnly(): void
    {
        $this->provideRunningOnlyTestSet();

        $output = new SpyOutput();
        $this->commandBus->dispatch(new BuildApp(
            output: $output,
            now: SerializableDateTime::fromString('2023-10-17 16:15:04')
        ));

        /** @var \App\Tests\Infrastructure\FileSystem\SpyFileSystem $fileSystem */
        $fileSystem = $this->getContainer()->get(FilesystemOperator::class);
        foreach ($fileSystem->getWrites() as $location => $content) {
            $this->snapshotName = $location;
            if (str_ends_with($location, '.json')) {
                $this->assertMatchesJsonSnapshot($content);
                continue;
            }
            $this->assertMatchesHtmlSnapshot($content);
        }
        $this->snapshotName = 'consoleOutput';
        $this->assertMatchesTextSnapshot($output);
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
