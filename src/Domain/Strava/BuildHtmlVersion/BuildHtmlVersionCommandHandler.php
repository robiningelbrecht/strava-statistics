<?php

namespace App\Domain\Strava\BuildHtmlVersion;

use App\Domain\Measurement\Length\Kilometer;
use App\Domain\Measurement\UnitSystem;
use App\Domain\Strava\Activity\ActivityHeatmapChartBuilder;
use App\Domain\Strava\Activity\ActivityHighlights;
use App\Domain\Strava\Activity\ActivityRepository;
use App\Domain\Strava\Activity\ActivityTotals;
use App\Domain\Strava\Activity\ActivityType;
use App\Domain\Strava\Activity\DaytimeStats\DaytimeStats;
use App\Domain\Strava\Activity\DaytimeStats\DaytimeStatsChartsBuilder;
use App\Domain\Strava\Activity\Eddington\Eddington;
use App\Domain\Strava\Activity\Eddington\EddingtonChartBuilder;
use App\Domain\Strava\Activity\HeartRateDistributionChartBuilder;
use App\Domain\Strava\Activity\Image\Image;
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
use App\Domain\Strava\Athlete\AthleteBirthday;
use App\Domain\Strava\Athlete\HeartRateZone;
use App\Domain\Strava\Athlete\TimeInHeartRateZoneChartBuilder;
use App\Domain\Strava\Athlete\Weight\AthleteWeightRepository;
use App\Domain\Strava\Calendar\Calendar;
use App\Domain\Strava\Calendar\Month;
use App\Domain\Strava\Calendar\Months;
use App\Domain\Strava\Challenge\ChallengeConsistency;
use App\Domain\Strava\Challenge\ChallengeRepository;
use App\Domain\Strava\DistanceBreakdown;
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
        private AthleteBirthday $athleteBirthday,
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

        $athleteId = $this->keyValueStore->find(Key::ATHLETE_ID)->getValue();
        $allActivities = $this->activityRepository->findAll();
        $allChallenges = $this->challengeRepository->findAll();
        $allBikes = $this->gearRepository->findAll();
        $allImages = $this->imageRepository->findAll();
        $allFtps = $this->ftpRepository->findAll();
        $allSegments = $this->segmentRepository->findAll();
        $alpeDuZwiftSegment = $allSegments->getAlpeDuZwiftSegment();

        $command->getOutput()->writeln('  => Calculating Eddington');
        $eddington = Eddington::fromActivities(
            activities: $allActivities,
            unitSystem: $this->unitSystem
        );

        $command->getOutput()->writeln('  => Calculating activity highlights');
        $activityHighlights = ActivityHighlights::fromActivities($allActivities);

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
        $monthlyStatistics = MonthlyStatistics::fromActivitiesAndChallenges(
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

            if ($cadenceStream = $streams->getByStreamType(StreamType::CADENCE)) {
                // @phpstan-ignore-next-line
                $activity->enrichWithMaxCadence(max($cadenceStream->getData()));
            }

            try {
                $ftp = $this->ftpRepository->find($activity->getStartDate());
                $activity->enrichWithFtp($ftp->getFtp());
            } catch (EntityNotFound) {
            }
            $activity->enrichWithAthleteBirthday($this->athleteBirthday);

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
                'eddingtonNumber' => $eddington->getNumber(),
                'completedChallenges' => count($allChallenges),
                'totalPhotoCount' => count($allImages),
                'lastUpdate' => $now,
                'athleteId' => $athleteId,
                'hasAlpeDuZwiftSegments' => $alpeDuZwiftSegment,
            ]),
        );

        $command->getOutput()->writeln('  => Building dashboard.html');
        $this->filesystem->write(
            'build/html/dashboard.html',
            $this->twig->load('html/dashboard.html.twig')->render([
                'mostRecentActivities' => $allActivities->slice(0, 5),
                'activityHighlights' => $activityHighlights,
                'intro' => ActivityTotals::fromActivities(
                    activities: $allActivities,
                    now: $now,
                ),
                'weeklyDistanceChart' => Json::encode(
                    WeeklyDistanceChartBuilder::fromActivities(
                        activities: $allActivities,
                        unitSystem: $this->unitSystem,
                        now: $now,
                    )->build(),
                ),
                'powerOutputs' => $bestPowerOutputs,
                'activityHeatmapChart' => Json::encode(
                    ActivityHeatmapChartBuilder::fromActivities(
                        activities: $allActivities,
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
                'distanceBreakdown' => DistanceBreakdown::fromActivities($allActivities),
                'trivia' => Trivia::fromActivities($allActivities),
                'ftpHistoryChart' => !$allFtps->isEmpty() ? Json::encode(
                    FtpHistoryChartBuilder::fromFtps(
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
                    monthlyStatistics: $monthlyStatistics,
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
                'powerOutputChart' => !empty($bestPowerOutputs) ? PowerOutputChartBuilder::fromBestPowerOutputs($bestPowerOutputs)
                    ->build() : null,
            ]),
        );

        $command->getOutput()->writeln('  => Building activities.html');
        $this->filesystem->write(
            'build/html/activities.html',
            $this->twig->load('html/activities.html.twig')->render(),
        );

        $command->getOutput()->writeln('  => Building photos.html');
        $this->filesystem->write(
            'build/html/photos.html',
            $this->twig->load('html/photos.html.twig')->render([
                'rideImagesCount' => count(array_filter($allImages, fn (Image $image) => ActivityType::RIDE === $image->getActivity()->getType())),
                'virtualRideImagesCount' => count(array_filter($allImages, fn (Image $image) => ActivityType::VIRTUAL_RIDE === $image->getActivity()->getType())),
                'images' => $allImages,
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
        $this->filesystem->write(
            'build/html/eddington.html',
            $this->twig->load('html/eddington.html.twig')->render([
                'eddingtonChart' => Json::encode(
                    EddingtonChartBuilder::fromEddington(
                        eddington: $eddington,
                        unitSystem: $this->unitSystem,
                    )->build(),
                ),
                'eddington' => $eddington,
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
                // Hacky solution to know what type of segment this is (Zwift or Rouvy).
                $segment->enrichWithDeviceName($activity->getDeviceName());
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
                'bikeStatistics' => GearStatistics::fromActivitiesAndGear(
                    activities: $allActivities,
                    bikes: $allBikes
                ),
                'distancePerMonthPerGearChart' => Json::encode(
                    DistancePerMonthPerGearChartBuilder::fromGearAndActivities(
                        gearCollection: $allBikes,
                        activityCollection: $allActivities,
                        unitSystem: $this->unitSystem,
                        months: $allMonths,
                    )->build()
                ),
                'distanceOverTimePerBike' => Json::encode(
                    DistanceOverTimePerGearChartBuilder::fromGearAndActivities(
                        gearCollection: $allBikes,
                        activityCollection: $allActivities,
                        unitSystem: $this->unitSystem,
                        now: $now,
                    )->build()
                ),
            ]),
        );

        $routesPerCountry = [];
        $routesInMostRiddenState = [];
        $mostRiddenState = $this->activityRepository->findMostRiddenState();
        foreach ($allActivities as $activity) {
            if (ActivityType::RIDE !== $activity->getType()) {
                continue;
            }
            if (!$polyline = $activity->getPolylineSummary()) {
                continue;
            }
            if (!$countryCode = $activity->getLocation()?->getCountryCode()) {
                continue;
            }
            $routesPerCountry[$countryCode][] = $polyline;
            if ($activity->getLocation()?->getState() === $mostRiddenState) {
                $routesInMostRiddenState[] = $polyline;
            }
        }

        $command->getOutput()->writeln('  => Building heatmap.html');
        $this->filesystem->write(
            'build/html/heatmap.html',
            $this->twig->load('html/heatmap.html.twig')->render([
                'routesPerCountry' => Json::encode($routesPerCountry),
                'routesInMostRiddenState' => Json::encode($routesInMostRiddenState),
            ]),
        );

        if ($alpeDuZwiftSegment) {
            $command->getOutput()->writeln('  => Building alpe-du-zwift.html');

            $segmentEfforts = $this->segmentEffortRepository->findBySegmentId($alpeDuZwiftSegment->getId());
            foreach ($segmentEfforts as $segmentEffort) {
                $activity = $allActivities->getByActivityId($segmentEffort->getActivityId());
                $segmentEffort->enrichWithActivity($activity);
            }

            $this->filesystem->write(
                'build/html/alpe-du-zwift.html',
                $this->twig->load('html/alpe-du-zwift.html.twig')->render([
                    'segmentEfforts' => $segmentEfforts,
                ]),
            );
        }

        $command->getOutput()->writeln('  => Building activity.html');
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
                            // @phpstan-ignore-next-line
                            athleteMaxHeartRate: $activity->getAthleteMaxHeartRate()
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
                    'activityHighlights' => $activityHighlights,
                ]),
                searchables: $activity->getSearchables(),
                // @phpstan-ignore-next-line
                sortValues: [
                    'start-date' => $activity->getStartDate()->getTimestamp(),
                    'distance' => $activity->getDistance()->toFloat(),
                    'elevation' => $activity->getElevation()->toFloat(),
                    'moving-time' => $activity->getMovingTimeInSeconds(),
                    'power' => $activity->getAveragePower(),
                    'speed' => round($activity->getAverageSpeed()->toFloat(), 1),
                    'heart-rate' => $activity->getAverageHeartRate(),
                    'calories' => $activity->getCalories(),
                ]
            );
        }

        $this->filesystem->write(
            'build/html/fetch-json/activity-data-table.json',
            Json::encode($dataDatableRows),
        );
    }
}
