<?php

declare(strict_types=1);

namespace App\Domain\Notification\SendNotification;

use App\Infrastructure\CQRS\Bus\Command;
use App\Infrastructure\CQRS\Bus\CommandHandler;
use App\Infrastructure\Notification\Ntfy\Ntfy;
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
            icon: Url::fromString('https://raw.githubusercontent.com/robiningelbrecht/strava-statistics/master/public/assets/images/logo.png')
        );
    }
}
