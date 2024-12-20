<?php

namespace App\Tests\Console;

use App\Console\BuildStravaActivityFilesConsoleCommand;
use App\Domain\Strava\MaxStravaUsageHasBeenReached;
use App\Infrastructure\CQRS\Bus\CommandBus;
use App\Infrastructure\CQRS\Bus\DomainCommand;
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
    private MockObject $reachedStravaApiRateLimits;

    public function testExecute(): void
    {
        $this->reachedStravaApiRateLimits
            ->expects($this->once())
            ->method('hasReached')
            ->willReturn(false);

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

    public function testExecuteWhenStravaLimitsHaveBeenReached(): void
    {
        $this->reachedStravaApiRateLimits
            ->expects($this->once())
            ->method('hasReached')
            ->willReturn(true);

        $this->commandBus
            ->expects($this->never())
            ->method('dispatch');

        $command = $this->getCommandInApplication('app:strava:build-files');
        $commandTester = new CommandTester($command);
        $commandTester->execute([
            'command' => $command->getName(),
        ]);
    }

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->commandBus = $this->createMock(CommandBus::class);
        $this->reachedStravaApiRateLimits = $this->createMock(MaxStravaUsageHasBeenReached::class);

        $this->buildStravaActivityFilesConsoleCommand = new BuildStravaActivityFilesConsoleCommand(
            $this->commandBus,
            $this->reachedStravaApiRateLimits,
            new FixedResourceUsage()
        );
    }

    protected function getConsoleCommand(): Command
    {
        return $this->buildStravaActivityFilesConsoleCommand;
    }
}
