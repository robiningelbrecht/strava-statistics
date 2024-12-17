<?php

declare(strict_types=1);

namespace App\Infrastructure\FileSystem;

use App\Infrastructure\ValueObject\String\KernelProjectDir;
use League\Flysystem\FilesystemOperator;

final readonly class FileSystemPermissionChecker implements PermissionChecker
{
    public function __construct(
        private KernelProjectDir $kernelProjectDir,
        private FilesystemOperator $filesystem,
    ) {
    }

    public function ensureWriteAccess(): void
    {
        $this->filesystem->write($this->kernelProjectDir.'/storage/database/test.txt', 'success');
        $this->filesystem->delete($this->kernelProjectDir.'/storage/database/test.txt');

        $this->filesystem->write($this->kernelProjectDir.'/storage/files/test.txt', 'success');
        $this->filesystem->delete($this->kernelProjectDir.'/storage/files/test.txt');
    }
}
