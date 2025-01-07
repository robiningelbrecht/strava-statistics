<?php

namespace App\Domain\Strava\BuildHtmlVersion;

use App\Domain\Measurement\Length\Kilometer;
use App\Domain\Measurement\UnitSystem;
use App\Domain\Strava\Activity\ActivityHeatmapChartBuilder;
use App\Domain\Strava\Activity\ActivityIntensity;
use App\Domain\Strava\Activity\ActivityRepository;
use App\Domain\Strava\Activity\ActivityTotals;
use App\Domain\Strava\Activity\ActivityType;
use App\Domain\Strava\Activity\DaytimeStats\DaytimeStats;
use App\Domain\Strava\Activity\DaytimeStats\DaytimeStatsChartsBuilder;
use App\Domain\Strava\Activity\DistanceBreakdown;
use App\Domain\Strava\Activity\Eddington\Eddington;
use App\Domain\Strava\Activity\Eddington\EddingtonChartBuilder;
use App\Domain\Strava\Activity\HeartRateDistributionChartBuilder;
use App\Domain\Strava\Activity\Image\ImageRepository;
use App\Domain\Strava\Activity\PowerDistributionChartBuilder;
use App\Domain\Strava\Activity\Stream\ActivityHeartRateRepository;
use App\Domain\Strava\Activity\Stream\ActivityPowerRepository;
use App\Domain\Strava\Activity\Stream\ActivityStreamRepository;
use App\Domain\Strava\Activity\Stream\PowerOutputChartBuilder;
use App\Domain\Strava\Activity\Stream\StreamType;
use App\Domain\Strava\Activity\Stream\StreamTypes;
use App\Domain\Strava\Activity\WeekdayStats\WeekdayStats;
use App\Domain\Strava\Activity\WeekdayStats\WeekdayStatsChartsBuilder;
use App\Domain\Strava\Activity\WeeklyDistanceChartBuilder;
use App\Domain\Strava\Activity\YearlyDistance\YearlyDistanceChartBuilder;
use App\Domain\Strava\Activity\YearlyDistance\YearlyStatistics;
use App\Domain\Strava\Athlete\Athlete;
use App\Domain\Strava\Athlete\HeartRateZone;
use App\Domain\Strava\Athlete\TimeInHeartRateZoneChartBuilder;
use App\Domain\Strava\Athlete\Weight\AthleteWeightRepository;
use App\Domain\Strava\Calendar\Calendar;
use App\Domain\Strava\Calendar\Month;
use App\Domain\Strava\Calendar\Months;
use App\Domain\Strava\Challenge\ChallengeRepository;
use App\Domain\Strava\Challenge\Consistency\ChallengeConsistency;
use App\Domain\Strava\Ftp\FtpHistoryChartBuilder;
use App\Domain\Strava\Ftp\FtpRepository;
use App\Domain\Strava\Gear\DistanceOverTimePerGearChartBuilder;
use App\Domain\Strava\Gear\DistancePerMonthPerGearChartBuilder;
use App\Domain\Strava\Gear\GearRepository;
use App\Domain\Strava\Gear\GearStatistics;
use App\Domain\Strava\MonthlyStatistics;
use App\Domain\Strava\Segment\Segment;
use App\Domain\Strava\Segment\SegmentEffort\SegmentEffortRepository;
use App\Domain\Strava\Segment\SegmentRepository;
use App\Domain\Strava\Trivia;
use App\Infrastructure\CQRS\Bus\Command;
use App\Infrastructure\CQRS\Bus\CommandHandler;
use App\Infrastructure\Exception\EntityNotFound;
use App\Infrastructure\KeyValue\Key;
use App\Infrastructure\KeyValue\KeyValueStore;
use App\Infrastructure\Serialization\Json;
use App\Infrastructure\Time\Clock\Clock;
use App\Infrastructure\ValueObject\DataTableRow;
use App\Infrastructure\ValueObject\Time\Years;
use League\Flysystem\FilesystemOperator;
use Twig\Environment;

final readonly class BuildHtmlVersionCommandHandler implements CommandHandler
{
    private const string APP_VERSION = 'v0.3.0';

    public function __construct(
        private ActivityRepository $activityRepository,
        private ChallengeRepository $challengeRepository,
        private GearRepository $gearRepository,
        private ImageRepository $imageRepository,
        private ActivityPowerRepository $activityPowerRepository,
        private ActivityStreamRepository $activityStreamRepository,
        private ActivityHeartRateRepository $activityHeartRateRepository,
        private AthleteWeightRepository $athleteWeightRepository,
        private SegmentRepository $segmentRepository,
        private SegmentEffortRepository $segmentEffortRepository,
        private FtpRepository $ftpRepository,
        private KeyValueStore $keyValueStore,
        private Athlete $athlete,
        private ActivityIntensity $activityIntensity,
        private UnitSystem $unitSystem,
        private Environment $twig,
        private FilesystemOperator $filesystem,
        private Clock $clock,
    ) {
    }

    public function handle(Command $command): void
    {
        assert($command instanceof BuildHtmlVersion);

        $now = $this->clock->getCurrentDateTimeImmutable();

        $athleteId = $this->keyValueStore->find(Key::ATHLETE_ID);
        $allActivities = $this->activityRepository->findAll();
        $activitiesPerActivityType = [];
        foreach (ActivityType::cases() as $activityType) {
            $activitiesPerActivityType[$activityType->value] = $allActivities->filterOnActivityType($activityType);
        }
        $importedSportTypes = $allActivities->getSportTypes();
        $allChallenges = $this->challengeRepository->findAll();
        $allGear = $this->gearRepository->findAll();
        $allImages = $this->imageRepository->findAll();
        $allFtps = $this->ftpRepository->findAll();
        $allSegments = $this->segmentRepository->findAll();

        $command->getOutput()->writeln('  => Calculating Eddington');
        $eddingtonPerActivityType = [];
        foreach (ActivityType::cases() as $activityType) {
            if (!$activityType->supportsEddington()) {
                continue;
            }
            if ($activitiesPerActivityType[$activityType->value]->isEmpty()) {
                continue;
            }
            $eddingtonPerActivityType[$activityType->value] = Eddington::fromActivities(
                activities: $activitiesPerActivityType[$activityType->value],
                unitSystem: $this->unitSystem
            );
        }

        $command->getOutput()->writeln('  => Calculating weekday stats');
        $weekdayStats = WeekdayStats::fromActivities($allActivities);

        $command->getOutput()->writeln('  => Calculating daytime stats');
        $dayTimeStats = DaytimeStats::fromActivities($allActivities);

        $allMonths = Months::create(
            startDate: $allActivities->getFirstActivityStartDate(),
            now: $now
        );
        $allYears = Years::create(
            startDate: $allActivities->getFirstActivityStartDate(),
            endDate: $now
        );

        $command->getOutput()->writeln('  => Calculating monthly stats');
        $monthlyStatistics = MonthlyStatistics::create(
            activities: $allActivities,
            challenges: $allChallenges,
            months: $allMonths,
        );
        $command->getOutput()->writeln('  => Calculating best power outputs');
        $bestPowerOutputs = $this->activityPowerRepository->findBest();

        $command->getOutput()->writeln('  => Enriching activities with data');
        /** @var \App\Domain\Strava\Activity\Activity $activity */
        foreach ($allActivities as $activity) {
            $activity->enrichWithBestPowerOutputs(
                $this->activityPowerRepository->findBestForActivity($activity->getId())
            );

            $streams = $this->activityStreamRepository->findByActivityAndStreamTypes(
                activityId: $activity->getId(),
                streamTypes: StreamTypes::fromArray([StreamType::CADENCE])
            );

            if (($cadenceStream = $streams->getByStreamType(StreamType::CADENCE)) && !empty($cadenceStream->getData())) {
                $activity->enrichWithMaxCadence(max($cadenceStream->getData()));
            }

            if ($activity->getGearId()) {
                $activity->enrichWithGearName(
                    $this->gearRepository->find($activity->getGearId())->getName()
                );
            }
        }

        /** @var \App\Domain\Strava\Ftp\Ftp $ftp */
        foreach ($allFtps as $ftp) {
            try {
                $ftp->enrichWithAthleteWeight(
                    $this->athleteWeightRepository->find($ftp->getSetOn())->getWeightInKg()
                );
            } catch (EntityNotFound) {
            }
        }

        $command->getOutput()->writeln('  => Building index.html');
        $this->filesystem->write(
            'build/html/index.html',
            $this->twig->load('html/index.html.twig')->render([
                'totalActivityCount' => count($allActivities),
                'eddingtons' => $eddingtonPerActivityType,
                'completedChallenges' => count($allChallenges),
                'totalPhotoCount' => count($allImages),
                'lastUpdate' => $now,
                'athleteId' => $athleteId,
                'currentAppVersion' => self::APP_VERSION,
            ]),
        );

        $command->getOutput()->writeln('  => Building dashboard.html');

        $weeklyDistanceCharts = [];

        foreach (ActivityType::cases() as $activityType) {
            $activities = $allActivities->filterOnActivityType($activityType);
            if ($activities->isEmpty()) {
                continue;
            }

            $chartData = WeeklyDistanceChartBuilder::create(
                activities: $activities,
                unitSystem: $this->unitSystem,
                now: $now,
            )->build();

            if (empty($chartData)) {
                continue;
            }

            $weeklyDistanceCharts[$activityType->value] = Json::encode($chartData);
        }

        $this->filesystem->write(
            'build/html/dashboard.html',
            $this->twig->load('html/dashboard.html.twig')->render([
                'mostRecentActivities' => $allActivities->slice(0, 5),
                'intro' => ActivityTotals::fromActivities(
                    activities: $allActivities,
                    now: $now,
                ),
                'weeklyDistanceCharts' => $weeklyDistanceCharts,
                'powerOutputs' => $bestPowerOutputs,
                'activityHeatmapChart' => Json::encode(
                    ActivityHeatmapChartBuilder::create(
                        activities: $allActivities,
                        activityIntensity: $this->activityIntensity,
                        now: $now,
                    )->build()
                ),
                'weekdayStatsChart' => Json::encode(
                    WeekdayStatsChartsBuilder::fromWeekdayStats($weekdayStats)->build(),
                ),
                'weekdayStats' => $weekdayStats,
                'daytimeStatsChart' => Json::encode(
                    DaytimeStatsChartsBuilder::fromDaytimeStats($dayTimeStats)->build(),
                ),
                'daytimeStats' => $dayTimeStats,
                'distanceBreakdown' => DistanceBreakdown::create(
                    activities: $activitiesPerActivityType[ActivityType::RIDE->value],
                    unitSystem: $this->unitSystem
                ),
                'trivia' => Trivia::fromActivities($allActivities),
                'ftpHistoryChart' => !$allFtps->isEmpty() ? Json::encode(
                    FtpHistoryChartBuilder::create(
                        ftps: $allFtps,
                        now: $now
                    )->build()
                ) : null,
                'timeInHeartRateZoneChart' => Json::encode(
                    TimeInHeartRateZoneChartBuilder::fromTimeInZones(
                        timeInSecondsInHeartRateZoneOne: $this->activityHeartRateRepository->findTotalTimeInSecondsInHeartRateZone(HeartRateZone::ONE),
                        timeInSecondsInHeartRateZoneTwo: $this->activityHeartRateRepository->findTotalTimeInSecondsInHeartRateZone(HeartRateZone::TWO),
                        timeInSecondsInHeartRateZoneThree: $this->activityHeartRateRepository->findTotalTimeInSecondsInHeartRateZone(HeartRateZone::THREE),
                        timeInSecondsInHeartRateZoneFour: $this->activityHeartRateRepository->findTotalTimeInSecondsInHeartRateZone(HeartRateZone::FOUR),
                        timeInSecondsInHeartRateZoneFive: $this->activityHeartRateRepository->findTotalTimeInSecondsInHeartRateZone(HeartRateZone::FIVE),
                    )->build(),
                ),
                'challengeConsistency' => ChallengeConsistency::create(
                    months: $allMonths,
                    activities: $allActivities
                ),
                'yearlyDistanceChart' => Json::encode(
                    YearlyDistanceChartBuilder::fromActivities(
                        activities: $allActivities,
                        unitSystem: $this->unitSystem,
                        now: $now
                    )->build()
                ),
                'yearlyStatistics' => YearlyStatistics::fromActivities(
                    activities: $allActivities,
                    years: $allYears
                ),
            ]),
        );

        if (!empty($bestPowerOutputs)) {
            $command->getOutput()->writeln('  => Building power-output.html');
            $this->filesystem->write(
                'build/html/power-output.html',
                $this->twig->load('html/power-output.html.twig')->render([
                    'powerOutputChart' => Json::encode(
                        PowerOutputChartBuilder::fromBestPowerOutputs($bestPowerOutputs)
                            ->build()
                    ),
                ]),
            );
        }

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
            $challengesGroupedByMonth[$challenge->getCreatedOn()->format('F Y')][] = $challenge;
        }
        $this->filesystem->write(
            'build/html/challenges.html',
            $this->twig->load('html/challenges.html.twig')->render([
                'challengesGroupedPerMonth' => $challengesGroupedByMonth,
            ]),
        );

        $command->getOutput()->writeln('  => Building eddington.html');

        $eddingtonChartsPerActivityType = [];
        foreach ($eddingtonPerActivityType as $activityType => $eddington) {
            $eddingtonChartsPerActivityType[$activityType] = Json::encode(
                EddingtonChartBuilder::fromEddington(
                    eddington: $eddington,
                    unitSystem: $this->unitSystem,
                )->build()
            );
        }

        $this->filesystem->write(
            'build/html/eddington.html',
            $this->twig->load('html/eddington.html.twig')->render([
                'eddingtons' => $eddingtonPerActivityType,
                'eddingtonCharts' => $eddingtonChartsPerActivityType,
                'distanceUnit' => Kilometer::from(1)->toUnitSystem($this->unitSystem)->getSymbol(),
            ]),
        );

        $command->getOutput()->writeln('  => Building segments.html');
        $dataDatableRows = [];
        /** @var Segment $segment */
        foreach ($allSegments as $segment) {
            $segmentEfforts = $this->segmentEffortRepository->findBySegmentId($segment->getId(), 10);
            $segment->enrichWithNumberOfTimesRidden($this->segmentEffortRepository->countBySegmentId($segment->getId()));
            $segment->enrichWithBestEffort($segmentEfforts->getBestEffort());

            /** @var \App\Domain\Strava\Segment\SegmentEffort\SegmentEffort $segmentEffort */
            foreach ($segmentEfforts as $segmentEffort) {
                $activity = $allActivities->getByActivityId($segmentEffort->getActivityId());
                // Hacky solution to know what type of segment this is.
                // @TODO: move this info to the Segment aggregate so we can simply query it.
                $segment->enrichWithDeviceName($activity->getDeviceName());
                $segment->enrichWithSportType($activity->getSportType());
                $segmentEffort->enrichWithActivity($activity);
            }

            $this->filesystem->write(
                'build/html/segment/'.$segment->getId().'.html',
                $this->twig->load('html/segment.html.twig')->render([
                    'segment' => $segment,
                    'segmentEfforts' => $segmentEfforts->slice(0, 10),
                ]),
            );

            $dataDatableRows[] = DataTableRow::create(
                markup: $this->twig->load('html/data-table/segment-data-table-row.html.twig')->render([
                    'segment' => $segment,
                ]),
                searchables: $segment->getSearchables(),
                filterables: [],
                sortValues: [
                    'name' => (string) $segment->getName(),
                    'distance' => round($segment->getDistance()->toFloat(), 2),
                    'max-gradient' => $segment->getMaxGradient(),
                    'ride-count' => $segment->getNumberOfTimesRidden(),
                ]
            );
        }

        $this->filesystem->write(
            'build/html/fetch-json/segment-data-table.json',
            Json::encode($dataDatableRows),
        );

        $this->filesystem->write(
            'build/html/segments.html',
            $this->twig->load('html/segments.html.twig')->render(),
        );

        $command->getOutput()->writeln('  => Building monthly-stats.html');
        $this->filesystem->write(
            'build/html/monthly-stats.html',
            $this->twig->load('html/monthly-stats.html.twig')->render([
                'monthlyStatistics' => $monthlyStatistics,
                'sportTypes' => $importedSportTypes,
            ]),
        );

        /** @var Month $month */
        foreach ($allMonths as $month) {
            $this->filesystem->write(
                'build/html/month/month-'.$month->getId().'.html',
                $this->twig->load('html/month.html.twig')->render([
                    'hasPreviousMonth' => $month->getId() != $allActivities->getFirstActivityStartDate()->format(Month::MONTH_ID_FORMAT),
                    'hasNextMonth' => $month->getId() != $now->format(Month::MONTH_ID_FORMAT),
                    'statistics' => $monthlyStatistics->getStatisticsForMonth($month),
                    'calendar' => Calendar::create(
                        month: $month,
                        activities: $allActivities
                    ),
                ]),
            );
        }

        $command->getOutput()->writeln('  => Building gear-stats.html');
        $this->filesystem->write(
            'build/html/gear-stats.html',
            $this->twig->load('html/gear-stats.html.twig')->render([
                'gearStatistics' => GearStatistics::fromActivitiesAndGear(
                    activities: $allActivities,
                    bikes: $allGear
                ),
                'distancePerMonthPerGearChart' => Json::encode(
                    DistancePerMonthPerGearChartBuilder::fromGearAndActivities(
                        gearCollection: $allGear,
                        activityCollection: $allActivities,
                        unitSystem: $this->unitSystem,
                        months: $allMonths,
                    )->build()
                ),
                'distanceOverTimePerGear' => Json::encode(
                    DistanceOverTimePerGearChartBuilder::fromGearAndActivities(
                        gearCollection: $allGear,
                        activityCollection: $allActivities,
                        unitSystem: $this->unitSystem,
                        now: $now,
                    )->build()
                ),
            ]),
        );

        $routesPerCountry = [];
        $routesInMostActiveState = [];
        $mostActiveState = $this->activityRepository->findMostActiveState();
        foreach ($allActivities as $activity) {
            if (!$activity->getSportType()->supportsReverseGeocoding()) {
                continue;
            }
            if (!$polyline = $activity->getPolylineSummary()) {
                continue;
            }
            if (!$countryCode = $activity->getLocation()?->getCountryCode()) {
                continue;
            }
            $routesPerCountry[$countryCode][] = $polyline;
            if ($activity->getLocation()?->getState() === $mostActiveState) {
                $routesInMostActiveState[] = $polyline;
            }
        }

        $command->getOutput()->writeln('  => Building heatmap.html');
        $this->filesystem->write(
            'build/html/heatmap.html',
            $this->twig->load('html/heatmap.html.twig')->render([
                'routesPerCountry' => Json::encode($routesPerCountry),
                'routesInMostRiddenState' => Json::encode($routesInMostActiveState),
            ]),
        );

        $command->getOutput()->writeln('  => Building activities.html');
        $this->filesystem->write(
            'build/html/activities.html',
            $this->twig->load('html/activities.html.twig')->render([
                'sportTypes' => $importedSportTypes,
            ]),
        );

        $dataDatableRows = [];
        foreach ($allActivities as $activity) {
            $heartRateData = $this->activityHeartRateRepository->findTimeInSecondsPerHeartRateForActivity($activity->getId());
            $powerData = $this->activityPowerRepository->findTimeInSecondsPerWattageForActivity($activity->getId());
            $leafletMap = $activity->getLeafletMap();

            $this->filesystem->write(
                'build/html/activity/'.$activity->getId().'.html',
                $this->twig->load('html/activity.html.twig')->render([
                    'activity' => $activity,
                    'leaflet' => $leafletMap ? [
                        'routes' => [$activity->getPolylineSummary()],
                        'map' => $leafletMap,
                    ] : null,
                    'heartRateDistributionChart' => $heartRateData ? Json::encode(
                        HeartRateDistributionChartBuilder::fromHeartRateData(
                            heartRateData: $heartRateData,
                            // @phpstan-ignore-next-line
                            averageHeartRate: $activity->getAverageHeartRate(),
                            athleteMaxHeartRate: $this->athlete->getMaxHeartRate($activity->getStartDate())
                        )->build(),
                    ) : null,
                    'powerDistributionChart' => $powerData ? Json::encode(
                        PowerDistributionChartBuilder::fromPowerData(
                            powerData: $powerData,
                            // @phpstan-ignore-next-line
                            averagePower: $activity->getAveragePower(),
                        )->build(),
                    ) : null,
                    'segmentEfforts' => $this->segmentEffortRepository->findByActivityId($activity->getId()),
                ]),
            );

            $dataDatableRows[] = DataTableRow::create(
                markup: $this->twig->load('html/data-table/activity-data-table-row.html.twig')->render([
                    'timeIntervals' => ActivityPowerRepository::TIME_INTERVAL_IN_SECONDS,
                    'activity' => $activity,
                ]),
                searchables: $activity->getSearchables(),
                filterables: $activity->getFilterables(),
                sortValues: $activity->getSortables()
            );
        }

        $this->filesystem->write(
            'build/html/fetch-json/activity-data-table.json',
            Json::encode($dataDatableRows),
        );
    }
}
