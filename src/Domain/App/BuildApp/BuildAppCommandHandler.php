<?php

namespace App\Domain\App\BuildApp;

use App\Domain\Strava\Activity\ActivitiesEnricher;
use App\Domain\Strava\Activity\ActivityTotals;
use App\Domain\Strava\Activity\ActivityTypeRepository;
use App\Domain\Strava\Activity\Eddington\Eddington;
use App\Domain\Strava\Activity\Eddington\EddingtonChart;
use App\Domain\Strava\Activity\Eddington\EddingtonHistoryChart;
use App\Domain\Strava\Activity\HeartRateChart;
use App\Domain\Strava\Activity\HeartRateDistributionChart;
use App\Domain\Strava\Activity\Image\ImageRepository;
use App\Domain\Strava\Activity\PowerDistributionChart;
use App\Domain\Strava\Activity\Route\RouteRepository;
use App\Domain\Strava\Activity\Split\ActivitySplitRepository;
use App\Domain\Strava\Activity\SportType\SportType;
use App\Domain\Strava\Activity\SportType\SportTypeRepository;
use App\Domain\Strava\Activity\Stream\ActivityHeartRateRepository;
use App\Domain\Strava\Activity\Stream\ActivityPowerRepository;
use App\Domain\Strava\Activity\Stream\ActivityStreamRepository;
use App\Domain\Strava\Activity\Stream\StreamType;
use App\Domain\Strava\Athlete\AthleteRepository;
use App\Domain\Strava\Calendar\Months;
use App\Domain\Strava\Challenge\ChallengeRepository;
use App\Domain\Strava\Gear\DistanceOverTimePerGearChart;
use App\Domain\Strava\Gear\DistancePerMonthPerGearChart;
use App\Domain\Strava\Gear\GearRepository;
use App\Domain\Strava\Gear\GearStatistics;
use App\Domain\Strava\Segment\Segment;
use App\Domain\Strava\Segment\SegmentEffort\SegmentEffortRepository;
use App\Domain\Strava\Segment\SegmentRepository;
use App\Domain\Strava\Trivia;
use App\Infrastructure\CQRS\Bus\Command;
use App\Infrastructure\CQRS\Bus\CommandHandler;
use App\Infrastructure\Exception\EntityNotFound;
use App\Infrastructure\Repository\Pagination;
use App\Infrastructure\Serialization\Json;
use App\Infrastructure\ValueObject\DataTableRow;
use App\Infrastructure\ValueObject\Measurement\Length\Kilometer;
use App\Infrastructure\ValueObject\Measurement\UnitSystem;
use League\Flysystem\FilesystemOperator;
use Symfony\Contracts\Translation\TranslatorInterface;
use Twig\Environment;

final readonly class BuildAppCommandHandler implements CommandHandler
{
    public function __construct(
        private ChallengeRepository $challengeRepository,
        private GearRepository $gearRepository,
        private ImageRepository $imageRepository,
        private ActivityPowerRepository $activityPowerRepository,
        private ActivityStreamRepository $activityStreamRepository,
        private ActivitySplitRepository $activitySplitRepository,
        private ActivityHeartRateRepository $activityHeartRateRepository,
        private AthleteRepository $athleteRepository,
        private SegmentRepository $segmentRepository,
        private SegmentEffortRepository $segmentEffortRepository,
        private SportTypeRepository $sportTypeRepository,
        private ActivityTypeRepository $activityTypeRepository,
        private RouteRepository $routeRepository,
        private ActivitiesEnricher $activitiesEnricher,
        private UnitSystem $unitSystem,
        private Environment $twig,
        private FilesystemOperator $filesystem,
        private TranslatorInterface $translator,
    ) {
    }

    public function handle(Command $command): void
    {
        assert($command instanceof BuildApp);

        $now = $command->getCurrentDateTime();

        $athlete = $this->athleteRepository->find();
        $allActivities = $this->activitiesEnricher->getEnrichedActivities();
        $importedSportTypes = $this->sportTypeRepository->findAll();
        $importedActivityTypes = $this->activityTypeRepository->findAll();
        $activitiesPerActivityType = $this->activitiesEnricher->getActivitiesPerActivityType();

        $allChallenges = $this->challengeRepository->findAll();
        $allGear = $this->gearRepository->findAll();
        $allImages = $this->imageRepository->findAll();

        $command->getOutput()->writeln('  => Calculating Eddington');
        $eddingtonPerActivityType = [];
        /** @var \App\Domain\Strava\Activity\ActivityType $activityType */
        foreach ($importedActivityTypes as $activityType) {
            if (!$activityType->supportsEddington()) {
                continue;
            }
            if ($activitiesPerActivityType[$activityType->value]->isEmpty()) {
                continue;
            }
            $eddington = Eddington::create(
                activities: $activitiesPerActivityType[$activityType->value],
                activityType: $activityType,
                unitSystem: $this->unitSystem
            );
            if ($eddington->getNumber() <= 0) {
                continue;
            }
            $eddingtonPerActivityType[$activityType->value] = $eddington;
        }

        $allMonths = Months::create(
            startDate: $allActivities->getFirstActivityStartDate(),
            now: $now
        );

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

        $command->getOutput()->writeln('  => Building eddington.html');

        $eddingtonChartsPerActivityType = [];
        $eddingtonHistoryChartsPerActivityType = [];
        foreach ($eddingtonPerActivityType as $activityType => $eddington) {
            $eddingtonChartsPerActivityType[$activityType] = Json::encode(
                EddingtonChart::create(
                    eddington: $eddington,
                    unitSystem: $this->unitSystem,
                    translator: $this->translator,
                )->build()
            );
            $eddingtonHistoryChartsPerActivityType[$activityType] = Json::encode(
                EddingtonHistoryChart::create(
                    eddington: $eddington,
                )->build()
            );
        }

        $this->filesystem->write(
            'build/html/eddington.html',
            $this->twig->load('html/eddington.html.twig')->render([
                'activityTypes' => $importedActivityTypes,
                'eddingtons' => $eddingtonPerActivityType,
                'eddingtonCharts' => $eddingtonChartsPerActivityType,
                'eddingtonHistoryCharts' => $eddingtonHistoryChartsPerActivityType,
                'distanceUnit' => Kilometer::from(1)->toUnitSystem($this->unitSystem)->getSymbol(),
            ]),
        );

        $command->getOutput()->writeln('  => Building segments.html');
        $dataDatableRows = [];
        $pagination = Pagination::fromOffsetAndLimit(0, 100);

        do {
            $segments = $this->segmentRepository->findAll($pagination);
            /** @var Segment $segment */
            foreach ($segments as $segment) {
                $segmentEfforts = $this->segmentEffortRepository->findBySegmentId($segment->getId(), 10);
                $segment->enrichWithNumberOfTimesRidden($this->segmentEffortRepository->countBySegmentId($segment->getId()));
                $segment->enrichWithBestEffort($segmentEfforts->getBestEffort());

                /** @var \App\Domain\Strava\Segment\SegmentEffort\SegmentEffort $segmentEffort */
                foreach ($segmentEfforts as $segmentEffort) {
                    $activity = $allActivities->getByActivityId($segmentEffort->getActivityId());
                    $segmentEffort->enrichWithActivity($activity);
                }

                $this->filesystem->write(
                    'build/html/segment/'.$segment->getId().'.html',
                    $this->twig->load('html/segment/segment.html.twig')->render([
                        'segment' => $segment,
                        'segmentEfforts' => $segmentEfforts->slice(0, 10),
                    ]),
                );

                $dataDatableRows[] = DataTableRow::create(
                    markup: $this->twig->load('html/segment/segment-data-table-row.html.twig')->render([
                        'segment' => $segment,
                    ]),
                    searchables: $segment->getSearchables(),
                    filterables: $segment->getFilterables(),
                    sortValues: $segment->getSortables(),
                    summables: []
                );
            }

            $pagination = $pagination->next();
        } while (!$segments->isEmpty());

        $this->filesystem->write(
            'build/html/fetch-json/segment-data-table.json',
            Json::encode($dataDatableRows),
        );

        $this->filesystem->write(
            'build/html/segments.html',
            $this->twig->load('html/segment/segments.html.twig')->render([
                'sportTypes' => $importedSportTypes,
                'totalSegmentCount' => $this->segmentRepository->count(),
            ]),
        );

        $command->getOutput()->writeln('  => Building gear-stats.html');
        $this->filesystem->write(
            'build/html/gear-stats.html',
            $this->twig->load('html/gear-stats.html.twig')->render([
                'gearStatistics' => GearStatistics::fromActivitiesAndGear(
                    activities: $allActivities,
                    bikes: $allGear
                ),
                'distancePerMonthPerGearChart' => Json::encode(
                    DistancePerMonthPerGearChart::create(
                        gearCollection: $allGear,
                        activityCollection: $allActivities,
                        unitSystem: $this->unitSystem,
                        months: $allMonths,
                    )->build()
                ),
                'distanceOverTimePerGear' => Json::encode(
                    DistanceOverTimePerGearChart::create(
                        gearCollection: $allGear,
                        activityCollection: $allActivities,
                        unitSystem: $this->unitSystem,
                        translator: $this->translator,
                        now: $now,
                    )->build()
                ),
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

        $command->getOutput()->writeln('  => Building activities.html');
        $this->filesystem->write(
            'build/html/activities.html',
            $this->twig->load('html/activity/activities.html.twig')->render([
                'sportTypes' => $importedSportTypes,
                'activityTotals' => $activityTotals,
            ]),
        );

        $dataDatableRows = [];
        foreach ($allActivities as $activity) {
            $timeInSecondsPerHeartRate = $this->activityHeartRateRepository->findTimeInSecondsPerHeartRateForActivity($activity->getId());
            $heartRateStream = null;
            if ($activity->getSportType()->getActivityType()->supportsHeartRateOverTimeChart()) {
                try {
                    $heartRateStream = $this->activityStreamRepository->findOneByActivityAndStreamType($activity->getId(), StreamType::HEART_RATE);
                } catch (EntityNotFound) {
                }
            }

            $timeInSecondsPerWattage = null;
            if ($activity->getSportType()->getActivityType()->supportsPowerDistributionChart()) {
                $timeInSecondsPerWattage = $this->activityPowerRepository->findTimeInSecondsPerWattageForActivity($activity->getId());
            }

            $activitySplits = $this->activitySplitRepository->findBy(
                activityId: $activity->getId(),
                unitSystem: $this->unitSystem
            );

            if (!$activitySplits->isEmpty() && $heartRateStream) {
                /** @var \App\Domain\Strava\Activity\Split\ActivitySplit $activitySplit */
                $sumSplitMovingTimeInSeconds = 0;
                foreach ($activitySplits as $activitySplit) {
                    $movingTimeInSeconds = $activitySplit->getMovingTimeInSeconds();
                    // Enrich ActivitySplit with average heart rate.
                    $heartRatesForCurrentSplit = array_slice(
                        array: $heartRateStream->getData(),
                        offset: $sumSplitMovingTimeInSeconds,
                        length: $movingTimeInSeconds
                    );
                    if (0 === count($heartRatesForCurrentSplit)) {
                        continue;
                    }
                    $averageHeartRate = (int) round(array_sum($heartRatesForCurrentSplit) / count($heartRatesForCurrentSplit));

                    $activitySplit->enrichWithAverageHeartRate($averageHeartRate);
                    $sumSplitMovingTimeInSeconds += $movingTimeInSeconds;
                }
            }

            $leafletMap = $activity->getLeafletMap();
            $this->filesystem->write(
                'build/html/activity/'.$activity->getId().'.html',
                $this->twig->load('html/activity/activity.html.twig')->render([
                    'activity' => $activity,
                    'leaflet' => $leafletMap ? [
                        'routes' => [$activity->getPolyline()],
                        'map' => $leafletMap,
                    ] : null,
                    'heartRateDistributionChart' => $timeInSecondsPerHeartRate && $activity->getAverageHeartRate() ? Json::encode(
                        HeartRateDistributionChart::fromHeartRateData(
                            heartRateData: $timeInSecondsPerHeartRate,
                            averageHeartRate: $activity->getAverageHeartRate(),
                            athleteMaxHeartRate: $athlete->getMaxHeartRate($activity->getStartDate())
                        )->build(),
                    ) : null,
                    'powerDistributionChart' => $timeInSecondsPerWattage && $activity->getAveragePower() ? Json::encode(
                        PowerDistributionChart::create(
                            powerData: $timeInSecondsPerWattage,
                            averagePower: $activity->getAveragePower(),
                        )->build(),
                    ) : null,
                    'segmentEfforts' => $this->segmentEffortRepository->findByActivityId($activity->getId()),
                    'splits' => $activitySplits,
                    'heartRateChart' => $heartRateStream?->getData() ? Json::encode(
                        HeartRateChart::create($heartRateStream)->build(),
                    ) : null,
                ]),
            );

            $dataDatableRows[] = DataTableRow::create(
                markup: $this->twig->load('html/activity/activity-data-table-row.html.twig')->render([
                    'timeIntervals' => ActivityPowerRepository::TIME_INTERVAL_IN_SECONDS,
                    'activity' => $activity,
                ]),
                searchables: $activity->getSearchables(),
                filterables: $activity->getFilterables(),
                sortValues: $activity->getSortables(),
                summables: $activity->getSummables($this->unitSystem),
            );
        }

        $this->filesystem->write(
            'build/html/fetch-json/activity-data-table.json',
            Json::encode($dataDatableRows),
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
