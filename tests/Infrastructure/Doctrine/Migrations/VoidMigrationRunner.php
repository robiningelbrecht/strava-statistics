<?php

declare(strict_types=1);

namespace App\Tests\Infrastructure\Doctrine;

use App\Infrastructure\Doctrine\MigrationRunner;

final readonly class VoidMigrationRunner implements MigrationRunner
{
    public function run(): void
    {
    }

    public function isAtLatestVersion(): bool
    {
        return true;
    }
}
