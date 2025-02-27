<?php

declare(strict_types=1);

namespace App\Domain\App\BuildBadgeSvg;

use App\Infrastructure\CQRS\Bus\DomainCommand;
use App\Infrastructure\ValueObject\Time\SerializableDateTime;

final class BuildBadgeSvg extends DomainCommand
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
