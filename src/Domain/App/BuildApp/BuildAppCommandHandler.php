<?php

namespace App\Domain\App\BuildApp;

use App\Domain\Strava\Activity\ActivitiesEnricher;
use App\Domain\Strava\Activity\ActivityTotals;
use App\Domain\Strava\Activity\Image\ImageRepository;
use App\Domain\Strava\Activity\Route\RouteRepository;
use App\Domain\Strava\Activity\SportType\SportType;
use App\Domain\Strava\Activity\SportType\SportTypeRepository;
use App\Domain\Strava\Athlete\AthleteRepository;
use App\Domain\Strava\Challenge\ChallengeRepository;
use App\Domain\Strava\Trivia;
use App\Infrastructure\CQRS\Bus\Command;
use App\Infrastructure\CQRS\Bus\CommandHandler;
use App\Infrastructure\Serialization\Json;
use League\Flysystem\FilesystemOperator;
use Twig\Environment;

final readonly class BuildAppCommandHandler implements CommandHandler
{
    public function __construct(
        private ChallengeRepository $challengeRepository,
        private ImageRepository $imageRepository,
        private AthleteRepository $athleteRepository,
        private SportTypeRepository $sportTypeRepository,
        private RouteRepository $routeRepository,
        private ActivitiesEnricher $activitiesEnricher,
        private Environment $twig,
        private FilesystemOperator $filesystem,
    ) {
    }

    public function handle(Command $command): void
    {
        assert($command instanceof BuildApp);

        $now = $command->getCurrentDateTime();

        $athlete = $this->athleteRepository->find();
        $allActivities = $this->activitiesEnricher->getEnrichedActivities();
        $importedSportTypes = $this->sportTypeRepository->findAll();
        $allChallenges = $this->challengeRepository->findAll();
        $allImages = $this->imageRepository->findAll();

        $activityTotals = ActivityTotals::create(
            activities: $allActivities,
            now: $now,
        );
        $trivia = Trivia::create($allActivities);

        $command->getOutput()->writeln('  => Building photos.html');
        $this->filesystem->write(
            'build/html/photos.html',
            $this->twig->load('html/photos.html.twig')->render([
                'images' => $allImages,
                'sportTypes' => $importedSportTypes,
            ]),
        );

        $command->getOutput()->writeln('  => Building challenges.html');
        $challengesGroupedByMonth = [];
        foreach ($allChallenges as $challenge) {
            $challengesGroupedByMonth[$challenge->getCreatedOn()->translatedFormat('F Y')][] = $challenge;
        }
        $this->filesystem->write(
            'build/html/challenges.html',
            $this->twig->load('html/challenges.html.twig')->render([
                'challengesGroupedPerMonth' => $challengesGroupedByMonth,
            ]),
        );

        $command->getOutput()->writeln('  => Building heatmap.html');
        $routes = $this->routeRepository->findAll();
        $this->filesystem->write(
            'build/html/heatmap.html',
            $this->twig->load('html/heatmap.html.twig')->render([
                'numberOfRoutes' => count($routes),
                'routes' => Json::encode($routes),
                'sportTypes' => $importedSportTypes->filter(
                    fn (SportType $sportType) => $sportType->supportsReverseGeocoding()
                ),
            ]),
        );

        $command->getOutput()->writeln('  => Building badge.svg');
        $this->filesystem->write(
            'storage/files/badge.svg',
            $this->twig->load('svg/svg-badge.html.twig')->render([
                'athlete' => $athlete,
                'activities' => $allActivities->slice(0, 5),
                'activityTotals' => $activityTotals,
                'trivia' => $trivia,
                'challengesCompleted' => count($allChallenges),
            ])
        );
        $this->filesystem->write(
            'build/html/badge.html',
            $this->twig->load('html/badge.html.twig')->render(),
        );
    }
}
