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
}
