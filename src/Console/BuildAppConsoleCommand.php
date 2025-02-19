<?php

namespace App\Console;

use App\Domain\App\BuildActivitiesHtml\BuildActivitiesHtml;
use App\Domain\App\BuildApp\BuildApp;
use App\Domain\App\BuildDashboardHtml\BuildDashboardHtml;
use App\Domain\App\BuildIndexHtml\BuildIndexHtml;
use App\Domain\App\BuildMonthlyStatsHtml\BuildMonthlyStatsHtml;
use App\Domain\App\ConfigureAppLocale\ConfigureAppLocale;
use App\Domain\Manifest\BuildManifest\BuildManifest;
use App\Domain\Notification\SendNotification\SendNotification;
use App\Domain\Strava\StravaDataImportStatus;
use App\Infrastructure\CQRS\Bus\CommandBus;
use App\Infrastructure\Doctrine\Migrations\MigrationRunner;
use App\Infrastructure\Time\Clock\Clock;
use App\Infrastructure\Time\ResourceUsage\ResourceUsage;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'app:strava:build-files', description: 'Build Strava files')]
final class BuildAppConsoleCommand extends Command
{
    public function __construct(
        private readonly CommandBus $commandBus,
        private readonly StravaDataImportStatus $stravaDataImportStatus,
        private readonly ResourceUsage $resourceUsage,
        private readonly MigrationRunner $migrationRunner,
        private readonly Clock $clock,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        if (!$this->migrationRunner->isAtLatestVersion()) {
            $output->writeln('<error>Your database is not up to date with the migration schema. Run the import command before building the HTML files</error>');

            return Command::FAILURE;
        }
        if (!$this->stravaDataImportStatus->isCompleted()) {
            $output->writeln('<error>Wait until all Strava data has been imported before building the app</error>');

            return Command::FAILURE;
        }
        $this->resourceUsage->startTimer();

        $now = $this->clock->getCurrentDateTimeImmutable();

        $output->writeln('Configuring locale...');
        $this->commandBus->dispatch(new ConfigureAppLocale());
        $output->writeln('Building Manifest...');
        $this->commandBus->dispatch(new BuildManifest());
        $output->writeln('Building App...');
        $output->writeln('  => Building index.html');
        $this->commandBus->dispatch(new BuildIndexHtml($now));
        $output->writeln('  => Building dashboard.html');
        $this->commandBus->dispatch(new BuildDashboardHtml($now));
        $output->writeln('  => Building activities.html');
        $this->commandBus->dispatch(new BuildActivitiesHtml($now));
        $output->writeln('  => Building monthly-stats.html');
        $this->commandBus->dispatch(new BuildMonthlyStatsHtml($now));
        $this->commandBus->dispatch(new BuildApp(
            output: $output,
            now: $now
        ));
        $this->commandBus->dispatch(new SendNotification(
            title: 'Build successful',
            message: 'New build of your Strava stats was successful',
            tags: ['+1']
        ));

        $this->resourceUsage->stopTimer();
        $output->writeln(sprintf(
            '<info>%s</info>',
            $this->resourceUsage->format(),
        ));

        return Command::SUCCESS;
    }
}
