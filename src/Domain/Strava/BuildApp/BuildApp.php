<?php

namespace App\Domain\Strava\BuildApp;

use App\Infrastructure\CQRS\Bus\DomainCommand;
use Symfony\Component\Console\Output\OutputInterface;

final class BuildApp extends DomainCommand
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
