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

    protected function getFileSystemOperators(): array
    {
        return [$this->getContainer()->get('file.storage')];
    }

    public function testHandleDuplicates(): void
    {
        $this->provideFullTestSet();

        $this->commandBus->dispatch($this->getDomainCommand());
        $this->commandBus->dispatch($this->getDomainCommand());

        /** @var FilesystemOperator $fileSystem */
        $fileSystem = $this->getContainer()->get('file.storage');
        $this->assertFileSystemWrites($fileSystem);
    }
}
