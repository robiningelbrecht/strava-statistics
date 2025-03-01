<?php

declare(strict_types=1);

namespace App\Domain\Strava\EFtp;

use App\Domain\Strava\Activity\ActivityType;
use App\Infrastructure\ValueObject\Time\SerializableDateTime;

final class EFtp
{
    private function __construct(
        private readonly SerializableDateTime $setOn,
        private readonly int $ftp,
        private readonly float $relativeEftp,
        private readonly ActivityType $activityType,
    ) {
    }

    public static function fromState(
        SerializableDateTime $setOn,
        EFtpOutput $ftp,
        ActivityType $type,
    ): self {
        return new self(
            setOn: $setOn,
            ftp: $ftp->getPower(),
            relativeEftp: $ftp->getRelativePower(),
            activityType: $type
        );
    }

    public function getSetOn(): SerializableDateTime
    {
        return $this->setOn;
    }

    public function getActivityType(): ActivityType
    {
        return $this->activityType;
    }

    public function getEFtp(): int
    {
        return $this->ftp;
    }

    public function getRelativeEftp(): float
    {
        return $this->relativeEftp;
    }
}
