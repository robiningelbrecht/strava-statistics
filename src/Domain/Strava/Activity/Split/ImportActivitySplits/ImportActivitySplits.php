<?php

namespace App\Domain\Strava\Activity\Split\ImportActivitySplits;

use App\Infrastructure\CQRS\Bus\DomainCommand;
use Symfony\Component\Console\Output\OutputInterface;

final class ImportActivitySplits extends DomainCommand
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
