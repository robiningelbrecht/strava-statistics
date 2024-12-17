<?php

declare(strict_types=1);

namespace App\Tests\Infrastructure\FileSystem;

use App\Infrastructure\FileSystem\PermissionChecker;
use League\Flysystem\UnableToWriteFile;

final readonly class UnwritablePermissionChecker implements PermissionChecker
{
    public function ensureWriteAccess(): void
    {
        throw new UnableToWriteFile();
    }
}
