<?php

declare(strict_types=1);

namespace App\Domain\Strava\Segment\DeleteOrphanedSegments;

use App\Infrastructure\CQRS\DomainCommand;

final readonly class DeleteOrphanedSegments extends DomainCommand
{
}
