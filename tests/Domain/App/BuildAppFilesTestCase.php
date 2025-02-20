<?php

declare(strict_types=1);

namespace App\Tests\Domain\App;

use App\Infrastructure\CQRS\Bus\CommandBus;
use App\Infrastructure\CQRS\Bus\DomainCommand;
use App\Tests\ContainerTestCase;
use App\Tests\Infrastructure\FileSystem\ProvideFileSystemWriteAssertion;
use App\Tests\ProvideTestData;
use League\Flysystem\FilesystemOperator;

abstract class BuildAppFilesTestCase extends ContainerTestCase
{
    use ProvideTestData;
    use ProvideFileSystemWriteAssertion;

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

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->commandBus = $this->getContainer()->get(CommandBus::class);
    }
}
