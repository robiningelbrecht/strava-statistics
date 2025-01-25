<?php

declare(strict_types=1);

namespace App\Domain\Strava\Activity\Split\DeleteActivitySplits;

use App\Domain\Strava\Activity\Split\ActivitySplitRepository;
use App\Infrastructure\CQRS\Bus\Command;
use App\Infrastructure\CQRS\Bus\CommandHandler;

final readonly class DeleteActivitySplitsCommandHandler implements CommandHandler
{
    public function __construct(
        private ActivitySplitRepository $activitySplitRepository,
    ) {
    }

    public function handle(Command $command): void
    {
        assert($command instanceof DeleteActivitySplits);

        $this->activitySplitRepository->deleteForActivity($command->getActivityId());
    }
}
