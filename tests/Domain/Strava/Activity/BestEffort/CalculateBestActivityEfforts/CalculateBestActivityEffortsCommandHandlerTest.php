<?php

namespace App\Tests\Domain\Strava\Activity\BestEffort\CalculateBestActivityEfforts;

use App\Domain\Strava\Activity\BestEffort\CalculateBestActivityEfforts\CalculateBestActivityEfforts;
use App\Infrastructure\CQRS\Bus\CommandBus;
use App\Infrastructure\Serialization\Json;
use App\Tests\ContainerTestCase;
use App\Tests\SpyOutput;
use Spatie\Snapshots\MatchesSnapshots;

class CalculateBestActivityEffortsCommandHandlerTest extends ContainerTestCase
{
    use MatchesSnapshots;

    private CommandBus $commandBus;

    public function testHandle(): void
    {
        $output = new SpyOutput();

        $this->commandBus->dispatch(new CalculateBestActivityEfforts($output));

        $this->assertMatchesTextSnapshot($output);
        $this->assertMatchesJsonSnapshot(
            Json::encode($this->getConnection()
                ->executeQuery('SELECT * FROM ActivityBestEffort')->fetchAllAssociative())
        );
    }

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->commandBus = $this->getContainer()->get(CommandBus::class);
    }
}
