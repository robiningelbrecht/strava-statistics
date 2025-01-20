<?php

declare(strict_types=1);

namespace App\Tests\Infrastructure\Doctrine\Migrations;

use App\Infrastructure\Doctrine\Migrations\MigrationRunner;

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
