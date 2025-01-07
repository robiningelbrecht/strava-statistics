<?php

declare(strict_types=1);

namespace App\Domain\Strava\Athlete\ImportAthlete;

use App\Infrastructure\CQRS\Bus\DomainCommand;
use Symfony\Component\Console\Output\OutputInterface;

final class ImportAthlete extends DomainCommand
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