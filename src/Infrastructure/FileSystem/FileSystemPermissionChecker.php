<?php

declare(strict_types=1);

namespace App\Infrastructure\FileSystem;

use League\Flysystem\FilesystemOperator;

final readonly class FileSystemPermissionChecker implements PermissionChecker
{
    public function __construct(
        private FilesystemOperator $fileStorage,
        private FilesystemOperator $databaseStorage,
    ) {
    }

    public function ensureWriteAccess(): void
    {
        $this->databaseStorage->write('test.txt', 'success');
        $this->databaseStorage->delete('test.txt');

        $this->fileStorage->write('test.txt', 'success');
        $this->fileStorage->delete('test.txt');
    }
}
