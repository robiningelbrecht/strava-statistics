<?php

declare(strict_types=1);

namespace App\Domain\Strava\Ftp\ImportFtp;

use App\Infrastructure\CQRS\DomainCommand;
use Symfony\Component\Console\Output\OutputInterface;

final class ImportFtp extends DomainCommand
{
    public function __construct(
        private readonly OutputInterface $output,
    ) {
    }

    public function getOutput(): OutputInterface
    {
        return $this->output;
    }
}
