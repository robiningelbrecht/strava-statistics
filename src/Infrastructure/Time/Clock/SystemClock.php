<?php

declare(strict_types=1);

namespace App\Infrastructure\Time\Clock;

use App\Infrastructure\ValueObject\Time\SerializableDateTime;

class SystemClock implements Clock
{
    public function getCurrentDateTimeImmutable(): SerializableDateTime
    {
        return SerializableDateTime::fromString('now');
    }
}
