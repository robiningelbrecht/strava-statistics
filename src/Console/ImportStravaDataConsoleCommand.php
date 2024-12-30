<?php

namespace App\Console;

use App\Domain\Strava\Activity\ImportActivities\ImportActivities;
use App\Domain\Strava\Activity\Stream\CalculateBestStreamAverages\CalculateBestStreamAverages;
use App\Domain\Strava\Activity\Stream\ImportActivityStreams\ImportActivityStreams;
use App\Domain\Strava\Athlete\Weight\ImportAthleteWeight\ImportAthleteWeight;
use App\Domain\Strava\Challenge\ImportChallenges\ImportChallenges;
use App\Domain\Strava\Ftp\ImportFtp\ImportFtp;
use App\Domain\Strava\Gear\ImportGear\ImportGear;
use App\Domain\Strava\MaxStravaUsageHasBeenReached;
use App\Domain\Strava\Segment\ImportSegments\ImportSegments;
use App\Infrastructure\CQRS\Bus\CommandBus;
use App\Infrastructure\Doctrine\MigrationRunner;
use App\Infrastructure\FileSystem\PermissionChecker;
use League\Flysystem\UnableToCreateDirectory;
use League\Flysystem\UnableToWriteFile;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'app:strava:import-data', description: 'Import Strava data')]
final class ImportStravaDataConsoleCommand extends Command
{
    public function __construct(
        private readonly CommandBus $commandBus,
        private readonly PermissionChecker $fileSystemPermissionChecker,
        private readonly MaxStravaUsageHasBeenReached $maxStravaUsageHasBeenReached,
        private readonly MigrationRunner $migrationRunner,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        try {
            $this->fileSystemPermissionChecker->ensureWriteAccess();
        } catch (UnableToWriteFile|UnableToCreateDirectory) {
            $output->writeln('<error>Make sure the container has write permissions to "storage/database" and "storage/files" on the host system</error>');

            return Command::SUCCESS;
        }

        $this->migrationRunner->run();

        if ($this->maxStravaUsageHasBeenReached->hasReached()) {
            $output->writeln('<error>You probably reached Strava API rate limits. You will need to import the rest of your activities tomorrow</error>');

            return Command::SUCCESS;
        }

        $this->fileSystemPermissionChecker->ensureWriteAccess();
        $this->maxStravaUsageHasBeenReached->clear();

        $this->commandBus->dispatch(new ImportActivities($output));
        $this->commandBus->dispatch(new ImportActivityStreams($output));
        $this->commandBus->dispatch(new ImportSegments($output));
        $this->commandBus->dispatch(new ImportGear($output));
        $this->commandBus->dispatch(new ImportChallenges($output));
        $this->commandBus->dispatch(new ImportFtp($output));
        $this->commandBus->dispatch(new ImportAthleteWeight($output));
        $this->commandBus->dispatch(new CalculateBestStreamAverages($output));

        return Command::SUCCESS;
    }
}
