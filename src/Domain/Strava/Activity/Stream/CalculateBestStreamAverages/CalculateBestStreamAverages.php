<?php

declare(strict_types=1);

namespace App\Domain\Strava\Activity\Stream\CalculateBestStreamAverages;

use App\Infrastructure\CQRS\Bus\DomainCommand;
use Symfony\Component\Console\Output\OutputInterface;

final class CalculateBestStreamAverages extends DomainCommand
{
    public function __construct(
        private readonly OutputInterface $output,
        private readonly bool $all = false,
    ) {
    }

    public function getAll(): bool
    {
        return $this->all;
    }

    public function getOutput(): OutputInterface
    {
        return $this->output;
    }
}
