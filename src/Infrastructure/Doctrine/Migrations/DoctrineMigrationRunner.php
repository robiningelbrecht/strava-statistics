<?php

declare(strict_types=1);

namespace App\Infrastructure\Doctrine;

use Symfony\Component\Process\Process;

final readonly class DoctrineMigrationRunner implements MigrationRunner
{
    public function run(): void
    {
        $process = new Process(
            command: ['/var/www/bin/console', 'doctrine:migrations:migrate', '--no-interaction'],
            timeout: null
        );
        $process->run();

        if (!$process->isSuccessful()) {
            throw new CouldNotRunMigrations($process->getErrorOutput());
        }
    }

    public function isAtLatestVersion(): bool
    {
        $process = new Process(
            command: ['/var/www/bin/console', 'doctrine:migrations:status'],
            timeout: null
        );
        $process->run();

        return $process->isSuccessful() && str_contains($process->getOutput(), 'Already at latest version');
    }
}
