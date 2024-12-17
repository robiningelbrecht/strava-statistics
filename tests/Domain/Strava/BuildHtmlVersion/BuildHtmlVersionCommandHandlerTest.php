<?php

namespace App\Tests\Domain\Strava\BuildHtmlVersion;

use App\Domain\Strava\BuildHtmlVersion\BuildHtmlVersion;
use App\Infrastructure\CQRS\Bus\CommandBus;
use App\Tests\ContainerTestCase;
use App\Tests\ProvideTestData;
use App\Tests\SpyOutput;
use League\Flysystem\FilesystemOperator;
use Spatie\Snapshots\MatchesSnapshots;

class BuildHtmlVersionCommandHandlerTest extends ContainerTestCase
{
    use MatchesSnapshots;
    use ProvideTestData;

    private CommandBus $commandBus;
    private string $snapshotName;

    public function testHandle(): void
    {
        $this->provideFullTestSet();

        $output = new SpyOutput();
        $this->commandBus->dispatch(new BuildHtmlVersion($output));

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
        $this->assertMatchesTextSnapshot($output);
    }

    protected function getSnapshotId(): string
    {
        return (new \ReflectionClass($this))->getShortName().'--'.
            $this->name().'--'.
            $this->snapshotName;
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->commandBus = $this->getContainer()->get(CommandBus::class);
    }
}
