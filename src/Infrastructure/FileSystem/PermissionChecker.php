<?php

declare(strict_types=1);

namespace App\Infrastructure\FileSystem;

interface PermissionChecker
{
    public function ensureWriteAccess(): void;
}
