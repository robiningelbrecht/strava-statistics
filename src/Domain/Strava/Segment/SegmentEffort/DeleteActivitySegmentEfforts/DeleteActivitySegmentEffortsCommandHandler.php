<?php

declare(strict_types=1);

namespace App\Domain\Strava\Segment\SegmentEffort\DeleteActivitySegmentEfforts;

use App\Domain\Strava\Segment\SegmentEffort\SegmentEffortRepository;
use App\Infrastructure\CQRS\Bus\Command;
use App\Infrastructure\CQRS\Bus\CommandHandler;

final readonly class DeleteActivitySegmentEffortsCommandHandler implements CommandHandler
{
    public function __construct(
        private SegmentEffortRepository $segmentEffortRepository,
    ) {
    }

    public function handle(Command $command): void
    {
        assert($command instanceof DeleteActivitySegmentEfforts);

        $segmentEfforts = $this->segmentEffortRepository->findByActivityId($command->getActivityId());
        if ($segmentEfforts->isEmpty()) {
            return;
        }

        foreach ($segmentEfforts as $segmentEffort) {
            $this->segmentEffortRepository->delete($segmentEffort);
        }
    }
}
