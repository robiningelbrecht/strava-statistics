<?php

declare(strict_types=1);

namespace App\Tests\Domain\Integration\Notification\Ntfy;

use App\Domain\Integration\Notification\Ntfy\Ntfy;
use App\Infrastructure\ValueObject\String\Url;

class SpyNotify implements Ntfy
{
    private array $notifications = [];

    public function sendNotification(string $title, string $message, array $tags, ?Url $click, ?Url $icon): void
    {
        $this->notifications[] = [
            'title' => $title,
            'message' => $message,
            'tags' => $tags,
            'click' => $click,
            'icon' => $icon,
        ];
    }

    public function getNotifications(): array
    {
        return $this->notifications;
    }
}
