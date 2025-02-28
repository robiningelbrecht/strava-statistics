<?php

namespace App\Tests\Domain\App\BuildGpxFiles;

use App\Domain\App\BuildGpxFiles\BuildGpxFiles;
use App\Infrastructure\CQRS\DomainCommand;
use App\Tests\Domain\App\BuildAppFilesTestCase;
use League\Flysystem\FilesystemOperator;

class BuildGpxFilesCommandHandlerTest extends BuildAppFilesTestCase
{
    protected function getDomainCommand(): DomainCommand
    {
        return new BuildGpxFiles();
    }

    public function testHandleDuplicates(): void
    {
        $this->provideFullTestSet();

        $this->commandBus->dispatch($this->getDomainCommand());
        $this->commandBus->dispatch($this->getDomainCommand());

        /** @var \App\Tests\Infrastructure\FileSystem\SpyFileSystem $fileSystem */
        $fileSystem = $this->getContainer()->get(FilesystemOperator::class);
        $this->assertFileSystemWrites($fileSystem->getWrites());
    }
}
