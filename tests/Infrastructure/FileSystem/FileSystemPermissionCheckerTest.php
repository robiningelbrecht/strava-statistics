<?php

namespace App\Tests\Infrastructure\FileSystem;

use App\Infrastructure\FileSystem\FileSystemPermissionChecker;
use League\Flysystem\FilesystemOperator;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class FileSystemPermissionCheckerTest extends TestCase
{
    private FileSystemPermissionChecker $fileSystemPermissionChecker;
    private MockObject $fileStorage;
    private MockObject $databaseStorage;

    public function testEnsureWriteAccess(): void
    {
        $this->fileStorage
            ->expects($this->once())
            ->method('write');

        $this->fileStorage
            ->expects($this->once())
            ->method('delete');

        $this->databaseStorage
            ->expects($this->once())
            ->method('write');

        $this->databaseStorage
            ->expects($this->once())
            ->method('delete');

        $this->fileSystemPermissionChecker->ensureWriteAccess();
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->fileSystemPermissionChecker = new FileSystemPermissionChecker(
            $this->fileStorage = $this->createMock(FilesystemOperator::class),
            $this->databaseStorage = $this->createMock(FilesystemOperator::class),
        );
    }
}
