<?php

declare(strict_types=1);

namespace App\Domain\App\BuildSegmentsHtml;

use App\Infrastructure\CQRS\DomainCommand;
use App\Infrastructure\ValueObject\Time\SerializableDateTime;

final class BuildSegmentsHtml extends DomainCommand
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
