<?php

declare(strict_types=1);

namespace App\Domain\App\BuildBadgeSvg;

use App\Domain\Strava\Activity\ActivitiesEnricher;
use App\Domain\Strava\Activity\ActivityTotals;
use App\Domain\Strava\Athlete\AthleteRepository;
use App\Domain\Strava\Challenge\ChallengeRepository;
use App\Domain\Strava\Trivia;
use App\Infrastructure\CQRS\Command;
use App\Infrastructure\CQRS\CommandHandler;
use League\Flysystem\FilesystemOperator;
use Twig\Environment;

final readonly class BuildBadgeSvgCommandHandler implements CommandHandler
{
    public function __construct(
        private AthleteRepository $athleteRepository,
        private ChallengeRepository $challengeRepository,
        private ActivitiesEnricher $activitiesEnricher,
        private Environment $twig,
        private FilesystemOperator $fileStorage,
        private FilesystemOperator $buildStorage,
    ) {
    }

    public function handle(Command $command): void
    {
        assert($command instanceof BuildBadgeSvg);

        $now = $command->getCurrentDateTime();
        $athlete = $this->athleteRepository->find();
        $activities = $this->activitiesEnricher->getEnrichedActivities();

        $activityTotals = ActivityTotals::getInstance(
            activities: $activities,
            now: $now,
        );
        $trivia = Trivia::getInstance($activities);

        $this->fileStorage->write(
            'badge.svg',
            $this->twig->load('svg/svg-badge.html.twig')->render([
                'athlete' => $athlete,
                'activities' => $activities->slice(0, 5),
                'activityTotals' => $activityTotals,
                'trivia' => $trivia,
                'challengesCompleted' => $this->challengeRepository->count(),
            ])
        );
        $this->buildStorage->write(
            'badge.html',
            $this->twig->load('html/badge.html.twig')->render(),
        );
    }
}
