<?php

declare(strict_types=1);

namespace App\Domain\Integration\Notification\Ntfy;

use App\Infrastructure\ValueObject\String\Url;

interface Ntfy
{
    /**
     * @param array<string> $tags
     */
    public function sendNotification(
        string $title,
        string $message,
        array $tags,
        ?Url $click,
        ?Url $icon,
    ): void;
}
