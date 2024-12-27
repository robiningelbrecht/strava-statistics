<?php

namespace App\Console;

use App\Domain\Strava\BuildHtmlVersion\BuildHtmlVersion;
use App\Domain\Strava\StravaDataImportStatus;
use App\Infrastructure\CQRS\Bus\CommandBus;
use App\Infrastructure\Time\ResourceUsage\ResourceUsage;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'app:strava:build-files', description: 'Build Strava files')]
final class BuildStravaActivityFilesConsoleCommand extends Command
{
    public function __construct(
        private readonly CommandBus $commandBus,
        private readonly StravaDataImportStatus $stravaDataImportStatus,
        private readonly ResourceUsage $resourceUsage,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        if (!$this->stravaDataImportStatus->isCompleted()) {
            $output->writeln('<error>Wait until all Strava data has been imported before building the app</error>');

            return Command::SUCCESS;
        }
        $this->resourceUsage->startTimer();

        $output->writeln('Building HTML...');
        $this->commandBus->dispatch(new BuildHtmlVersion($output));

        $this->resourceUsage->stopTimer();
        $output->writeln(sprintf(
            '<info>%s</info>',
            $this->resourceUsage->format(),
        ));

        return Command::SUCCESS;
    }
}
