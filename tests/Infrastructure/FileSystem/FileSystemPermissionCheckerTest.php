<?php

namespace App\Tests\Infrastructure\FileSystem;

use App\Infrastructure\FileSystem\FileSystemPermissionChecker;
use App\Infrastructure\ValueObject\String\KernelProjectDir;
use League\Flysystem\FilesystemOperator;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class FileSystemPermissionCheckerTest extends TestCase
{
    private FileSystemPermissionChecker $fileSystemPermissionChecker;
    private MockObject $filesystem;

    public function testEnsureWriteAccess(): void
    {
        $this->filesystem
            ->expects($this->exactly(2))
            ->method('write');

        $this->filesystem
            ->expects($this->exactly(2))
            ->method('delete');

        $this->fileSystemPermissionChecker->ensureWriteAccess();
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->fileSystemPermissionChecker = new FileSystemPermissionChecker(
            KernelProjectDir::fromString('root'),
            $this->filesystem = $this->createMock(FilesystemOperator::class),
        );
    }
}
