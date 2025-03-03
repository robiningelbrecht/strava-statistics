<?php

declare(strict_types=1);

namespace App\Console;

use App\Domain\App\AppVersion;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(name: 'app:debug:environment', description: 'Outputs environment related debugging info')]
final class DebugEnvironmentConsoleCommand extends Command
{
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $io->title('Statistics for Strava');
        $io->text('Please copy all this output into the description of the bug ticket');
        $io->warning('Do not forget to redact sensitive information');

        $table = new Table($output);
        $table
            ->setHeaders(['ENV variable', 'Value'])
            ->setRows([
                ['APP_VERSION', AppVersion::getSemanticVersion()],
                ...array_map(fn (string $key, string $value) => [$key, $value], array_keys(getenv()), getenv()),
            ]);
        $table->render();

        return Command::SUCCESS;
    }
}
