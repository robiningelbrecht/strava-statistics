<?php

declare(strict_types=1);

namespace App\Infrastructure\Doctrine;

interface MigrationRunner
{
    public function run(): void;

    public function isAtLatestVersion(): bool;
}
