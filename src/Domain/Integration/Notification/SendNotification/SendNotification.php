<?php

declare(strict_types=1);

namespace App\Domain\Integration\Notification\SendNotification;

use App\Infrastructure\CQRS\Bus\DomainCommand;

final class SendNotification extends DomainCommand
{
    public function __construct(
        private readonly string $title,
        private readonly string $message,
        /** @var array<string> */
        private readonly array $tags,
    ) {
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function getMessage(): string
    {
        return $this->message;
    }

    /**
     * @return array<string>
     */
    public function getTags(): array
    {
        return $this->tags;
    }
}
