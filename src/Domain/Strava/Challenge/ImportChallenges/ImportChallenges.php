<?php

namespace App\Domain\Strava\Challenge\ImportChallenges;

use App\Infrastructure\CQRS\DomainCommand;
use Symfony\Component\Console\Output\OutputInterface;

final class ImportChallenges extends DomainCommand
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
