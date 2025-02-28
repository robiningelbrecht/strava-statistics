<?php

namespace App\Tests\Domain\App\BuildDashboardHtml;

use App\Domain\App\BuildDashboardHtml\BuildDashboardHtml;
use App\Infrastructure\CQRS\DomainCommand;
use App\Infrastructure\ValueObject\Time\SerializableDateTime;
use App\Tests\Domain\App\BuildAppFilesTestCase;
use League\Flysystem\FilesystemOperator;

class BuildDashboardHtmlCommandHandlerTest extends BuildAppFilesTestCase
{
    protected function getDomainCommand(): DomainCommand
    {
        return new BuildDashboardHtml(SerializableDateTime::fromString('2023-10-17 16:15:04'));
    }

    public function testHandleForRunningActivitiesOnly(): void
    {
        $this->provideRunningOnlyTestSet();

        $this->commandBus->dispatch($this->getDomainCommand());

        /** @var \App\Tests\Infrastructure\FileSystem\SpyFileSystem $fileSystem */
        $fileSystem = $this->getContainer()->get(FilesystemOperator::class);
        $this->assertFileSystemWrites($fileSystem->getWrites());
    }
}
