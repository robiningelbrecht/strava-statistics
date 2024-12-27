<?php

namespace App\Tests\Console;

use App\Console\BuildStravaActivityFilesConsoleCommand;
use App\Domain\Strava\StravaDataImportStatus;
use App\Infrastructure\CQRS\Bus\CommandBus;
use App\Infrastructure\CQRS\Bus\DomainCommand;
use App\Infrastructure\KeyValue\Key;
use App\Infrastructure\KeyValue\KeyValue;
use App\Infrastructure\KeyValue\KeyValueStore;
use App\Infrastructure\KeyValue\Value;
use App\Infrastructure\Serialization\Json;
use App\Tests\Infrastructure\Time\ResourceUsage\FixedResourceUsage;
use PHPUnit\Framework\MockObject\MockObject;
use Spatie\Snapshots\MatchesSnapshots;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

class BuildStravaActivityFilesConsoleCommandTest extends ConsoleCommandTestCase
{
    use MatchesSnapshots;

    private BuildStravaActivityFilesConsoleCommand $buildStravaActivityFilesConsoleCommand;
    private MockObject $commandBus;

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

        $this->commandBus
            ->expects($this->any())
            ->method('dispatch')
            ->willReturnCallback(fn (DomainCommand $command) => $this->assertMatchesJsonSnapshot(Json::encode($command)));

        $command = $this->getCommandInApplication('app:strava:build-files');
        $commandTester = new CommandTester($command);
        $commandTester->execute([
            'command' => $command->getName(),
        ]);

        $this->assertMatchesTextSnapshot($commandTester->getDisplay());
    }

    public function testExecuteWhenStravaImportIsNotCompleted(): void
    {
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

        $this->buildStravaActivityFilesConsoleCommand = new BuildStravaActivityFilesConsoleCommand(
            $this->commandBus,
            $this->getContainer()->get(StravaDataImportStatus::class),
            new FixedResourceUsage()
        );
    }

    protected function getConsoleCommand(): Command
    {
        return $this->buildStravaActivityFilesConsoleCommand;
    }
}
