<?php

declare(strict_types=1);

namespace App\Domain\Integration\Notification\SendNotification;

use App\Domain\Integration\Notification\Ntfy\Ntfy;
use App\Infrastructure\CQRS\Command;
use App\Infrastructure\CQRS\CommandHandler;
use App\Infrastructure\ValueObject\String\Url;

final readonly class SendNotificationCommandHandler implements CommandHandler
{
    public function __construct(
        private Ntfy $ntfy,
    ) {
    }

    public function handle(Command $command): void
    {
        assert($command instanceof SendNotification);

        $this->ntfy->sendNotification(
            title: $command->getTitle(),
            message: $command->getMessage(),
            tags: $command->getTags(),
            click: null,
            icon: Url::fromString('https://raw.githubusercontent.com/robiningelbrecht/strava-statistics/master/public/assets/images/manifest/icon-192.png')
        );
    }
}
