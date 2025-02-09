<?php

namespace App\Tests\Domain\Notification\SendNotification;

use App\Domain\Notification\SendNotification\SendNotification;
use App\Infrastructure\CQRS\Bus\CommandBus;
use App\Infrastructure\Notification\Ntfy\Ntfy;
use App\Infrastructure\Serialization\Json;
use App\Tests\ContainerTestCase;
use Spatie\Snapshots\MatchesSnapshots;

class SendNotificationCommandHandlerTest extends ContainerTestCase
{
    use MatchesSnapshots;

    private CommandBus $commandBus;

    public function testHandle(): void
    {
        $this->commandBus->dispatch(new SendNotification(
            title: 'le title',
            message: 'le message',
            tags: ['tag1', 'tag2'],
        ));

        /** @var \App\Tests\Infrastructure\Notification\Ntfy\SpyNotify $ntfy */
        $ntfy = $this->getContainer()->get(Ntfy::class);
        $this->assertMatchesJsonSnapshot(Json::encode($ntfy->getNotifications()));
    }

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->commandBus = $this->getContainer()->get(CommandBus::class);
    }
}
