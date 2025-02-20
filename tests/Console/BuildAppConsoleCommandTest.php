<?php

namespace App\Tests\Console;

use App\Console\BuildAppConsoleCommand;
use App\Domain\Strava\StravaDataImportStatus;
use App\Infrastructure\CQRS\Bus\CommandBus;
use App\Infrastructure\CQRS\Bus\DomainCommand;
use App\Infrastructure\Doctrine\Migrations\MigrationRunner;
use App\Infrastructure\KeyValue\Key;
use App\Infrastructure\KeyValue\KeyValue;
use App\Infrastructure\KeyValue\KeyValueStore;
use App\Infrastructure\KeyValue\Value;
use App\Infrastructure\Serialization\Json;
use App\Infrastructure\ValueObject\Time\SerializableDateTime;
use App\Tests\Infrastructure\Time\Clock\PausedClock;
use App\Tests\Infrastructure\Time\ResourceUsage\FixedResourceUsage;
use PHPUnit\Framework\MockObject\MockObject;
use Spatie\Snapshots\MatchesSnapshots;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

class BuildAppConsoleCommandTest extends ConsoleCommandTestCase
{
    use MatchesSnapshots;

    private BuildAppConsoleCommand $buildAppConsoleCommand;
    private MockObject $commandBus;
    private MockObject $migrationRunner;

    public function testExecute(): void
    {
        $this->getContainer()->get(KeyValueStore::class)->save(KeyValue::fromState(
            Key::STRAVA_ACTIVITY_IMPORT,
            Value::fromString('yes')
        ));

        $this->getContainer()->get(KeyValueStore::class)->save(KeyValue::fromState(
            Key::STRAVA_GEAR_IMPORT,
            Value::fromString('yes')
        ));

        $this->migrationRunner
            ->expects($this->once())
            ->method('isAtLatestVersion')
            ->willReturn(true);

        $dispatchedCommands = [];
        $this->commandBus
            ->expects($this->any())
            ->method('dispatch')
            ->willReturnCallback(function (DomainCommand $command) use (&$dispatchedCommands) {
                $dispatchedCommands[] = $command;
            });

        $command = $this->getCommandInApplication('app:strava:build-files');
        $commandTester = new CommandTester($command);
        $commandTester->execute([
            'command' => $command->getName(),
        ]);

        $this->assertMatchesTextSnapshot($commandTester->getDisplay());
        $this->assertMatchesJsonSnapshot(Json::encode($dispatchedCommands));
    }

    public function testExecuteWhenStravaImportIsNotCompleted(): void
    {
        $this->migrationRunner
            ->expects($this->once())
            ->method('isAtLatestVersion')
            ->willReturn(true);

        $this->commandBus
            ->expects($this->never())
            ->method('dispatch');

        $command = $this->getCommandInApplication('app:strava:build-files');
        $commandTester = new CommandTester($command);
        $commandTester->execute([
            'command' => $command->getName(),
        ]);

        $this->assertMatchesTextSnapshot($commandTester->getDisplay());
    }

    public function testExecuteWhenMigrationSchemaNotUpToDate(): void
    {
        $this->migrationRunner
            ->expects($this->once())
            ->method('isAtLatestVersion')
            ->willReturn(false);

        $this->commandBus
            ->expects($this->never())
            ->method('dispatch');

        $command = $this->getCommandInApplication('app:strava:build-files');
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

        $this->commandBus = $this->createMock(CommandBus::class);

        $this->buildAppConsoleCommand = new BuildAppConsoleCommand(
            $this->commandBus,
            $this->getContainer()->get(StravaDataImportStatus::class),
            new FixedResourceUsage(),
            $this->migrationRunner = $this->createMock(MigrationRunner::class),
            PausedClock::on(SerializableDateTime::fromString('2023-10-17 16:15:04'))
        );
    }

    protected function getConsoleCommand(): Command
    {
        return $this->buildAppConsoleCommand;
    }
}
