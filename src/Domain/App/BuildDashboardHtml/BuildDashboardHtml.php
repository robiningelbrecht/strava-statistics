<?php

declare(strict_types=1);

namespace App\Domain\App\BuildDashboardHtml;

use App\Infrastructure\CQRS\DomainCommand;
use App\Infrastructure\ValueObject\Time\SerializableDateTime;

final class BuildDashboardHtml extends DomainCommand
{
    public function __construct(
        private readonly SerializableDateTime $now,
    ) {
    }

    public function getCurrentDateTime(): SerializableDateTime
    {
        return $this->now;
    }
}
