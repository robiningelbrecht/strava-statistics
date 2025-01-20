<?php

declare(strict_types=1);

namespace App\Infrastructure\Doctrine\Migrations;

interface MigrationRunner
{
    public function run(): void;

    public function isAtLatestVersion(): bool;
}
