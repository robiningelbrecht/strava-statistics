<?php

namespace App\Tests\Console;

use App\Console\ImportStravaDataConsoleCommand;
use App\Domain\Strava\MaxStravaUsageHasBeenReached;
use App\Infrastructure\CQRS\Bus\CommandBus;
use App\Infrastructure\CQRS\Bus\DomainCommand;
use App\Infrastructure\Doctrine\MigrationRunner;
use App\Infrastructure\Serialization\Json;
use App\Tests\Infrastructure\FileSystem\SuccessfulPermissionChecker;
use App\Tests\Infrastructure\FileSystem\UnwritablePermissionChecker;
use PHPUnit\Framework\MockObject\MockObject;
use Spatie\Snapshots\MatchesSnapshots;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

class ImportStravaDataConsoleCommandTest extends ConsoleCommandTestCase
{
    use MatchesSnapshots;

    private ImportStravaDataConsoleCommand $importStravaDataConsoleCommand;
    private MockObject $commandBus;
    private MockObject $maxStravaUsageHasBeenReached;
    private MockObject $migrationRunner;

    public function testExecute(): void
    {
        $this->maxStravaUsageHasBeenReached
            ->expects($this->once())
            ->method('clear');

        $this->migrationRunner
            ->expects($this->once())
            ->method('run');

        $this->maxStravaUsageHasBeenReached
            ->expects($this->once())
            ->method('hasReached')
            ->willReturn(false);

        $this->commandBus
            ->expects($this->any())
            ->method('dispatch')
            ->willReturnCallback(fn (DomainCommand $command) => $this->assertMatchesJsonSnapshot(Json::encode($command)));

        $command = $this->getCommandInApplication('app:strava:import-data');
        $commandTester = new CommandTester($command);
        $commandTester->execute([
            'command' => $command->getName(),
        ]);
    }

    public function testExecuteWithMaxStravaUsageReached(): void
    {
        $this->maxStravaUsageHasBeenReached
            ->expects($this->never())
            ->method('clear');

        $this->migrationRunner
            ->expects($this->never())
            ->method('run');

        $this->commandBus
            ->expects($this->never())
            ->method('dispatch');

        $this->maxStravaUsageHasBeenReached
            ->expects($this->once())
            ->method('hasReached')
            ->willReturn(true);

        $command = $this->getCommandInApplication('app:strava:import-data');
        $commandTester = new CommandTester($command);
        $commandTester->execute([
            'command' => $command->getName(),
        ]);

        $this->assertMatchesTextSnapshot($commandTester->getDisplay());
    }

    public function testExecuteWithInsufficientPermissions(): void
    {
        $this->importStravaDataConsoleCommand = new ImportStravaDataConsoleCommand(
            $this->commandBus = $this->createMock(CommandBus::class),
            new UnwritablePermissionChecker(),
            $this->maxStravaUsageHasBeenReached = $this->createMock(MaxStravaUsageHasBeenReached::class),
            $this->migrationRunner = $this->createMock(MigrationRunner::class)
        );

        $this->maxStravaUsageHasBeenReached
            ->expects($this->never())
            ->method('clear');

        $this->migrationRunner
            ->expects($this->never())
            ->method('run');

        $this->maxStravaUsageHasBeenReached
            ->expects($this->never())
            ->method('hasReached');

        $this->commandBus
            ->expects($this->never())
            ->method('dispatch');

        $command = $this->getCommandInApplication('app:strava:import-data');
        $commandTester = new CommandTester($command);
        $commandTester->execute([
            'command' => $command->getName(),
        ]);

        $this->assertMatchesTextSnapshot($commandTester->getDisplay());
    }

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->importStravaDataConsoleCommand = new ImportStravaDataConsoleCommand(
            $this->commandBus = $this->createMock(CommandBus::class),
            new SuccessfulPermissionChecker(),
            $this->maxStravaUsageHasBeenReached = $this->createMock(MaxStravaUsageHasBeenReached::class),
            $this->migrationRunner = $this->createMock(MigrationRunner::class)
        );
    }

    protected function getConsoleCommand(): Command
    {
        return $this->importStravaDataConsoleCommand;
    }
}
