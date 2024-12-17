<?php

declare(strict_types=1);

namespace App\Tests\Infrastructure\FileSystem;

use App\Infrastructure\FileSystem\PermissionChecker;

final readonly class SuccessfulPermissionChecker implements PermissionChecker
{
    public function ensureWriteAccess(): void
    {
    }
}
