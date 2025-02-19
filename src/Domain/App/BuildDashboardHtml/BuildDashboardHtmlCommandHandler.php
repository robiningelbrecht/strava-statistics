<?php

declare(strict_types=1);

namespace App\Domain\App\BuildDashboardHtml;

use App\Domain\Strava\Activity\ActivitiesEnricher;
use App\Domain\Strava\Activity\ActivityHeatmapChart;
use App\Domain\Strava\Activity\ActivityIntensity;
use App\Domain\Strava\Activity\ActivityTotals;
use App\Domain\Strava\Activity\ActivityType;
use App\Domain\Strava\Activity\DaytimeStats\DaytimeStats;
use App\Domain\Strava\Activity\DaytimeStats\DaytimeStatsCharts;
use App\Domain\Strava\Activity\DistanceBreakdown;
use App\Domain\Strava\Activity\Stream\ActivityHeartRateRepository;
use App\Domain\Strava\Activity\Stream\ActivityPowerRepository;
use App\Domain\Strava\Activity\Stream\PowerOutputChart;
use App\Domain\Strava\Activity\WeekdayStats\WeekdayStats;
use App\Domain\Strava\Activity\WeekdayStats\WeekdayStatsChart;
use App\Domain\Strava\Activity\WeeklyDistanceChart;
use App\Domain\Strava\Activity\YearlyDistance\YearlyDistanceChart;
use App\Domain\Strava\Activity\YearlyDistance\YearlyStatistics;
use App\Domain\Strava\Athlete\HeartRateZone;
use App\Domain\Strava\Athlete\TimeInHeartRateZoneChart;
use App\Domain\Strava\Athlete\Weight\AthleteWeightRepository;
use App\Domain\Strava\Calendar\Months;
use App\Domain\Strava\Challenge\Consistency\ChallengeConsistency;
use App\Domain\Strava\Ftp\FtpHistoryChart;
use App\Domain\Strava\Ftp\FtpRepository;
use App\Domain\Strava\Trivia;
use App\Infrastructure\CQRS\Bus\Command;
use App\Infrastructure\CQRS\Bus\CommandHandler;
use App\Infrastructure\Exception\EntityNotFound;
use App\Infrastructure\Serialization\Json;
use App\Infrastructure\ValueObject\Measurement\UnitSystem;
use App\Infrastructure\ValueObject\Time\Years;
use League\Flysystem\FilesystemOperator;
use Symfony\Contracts\Translation\TranslatorInterface;
use Twig\Environment;

final readonly class BuildDashboardHtmlCommandHandler implements CommandHandler
{
    public function __construct(
        private ActivityHeartRateRepository $activityHeartRateRepository,
        private ActivityPowerRepository $activityPowerRepository,
        private FtpRepository $ftpRepository,
        private AthleteWeightRepository $athleteWeightRepository,
        private ActivitiesEnricher $activitiesEnricher,
        private ActivityIntensity $activityIntensity,
        private UnitSystem $unitSystem,
        private Environment $twig,
        private FilesystemOperator $filesystem,
        private TranslatorInterface $translator,
    ) {
    }

    public function handle(Command $command): void
    {
        assert($command instanceof BuildDashboardHtml);

        $now = $command->getCurrentDateTime();
        $allActivities = $this->activitiesEnricher->getEnrichedActivities();
        $activitiesPerActivityType = $this->activitiesEnricher->getActivitiesPerActivityType();
        $allFtps = $this->ftpRepository->findAll();
        $allYears = Years::create(
            startDate: $allActivities->getFirstActivityStartDate(),
            endDate: $now
        );
        $allMonths = Months::create(
            startDate: $allActivities->getFirstActivityStartDate(),
            now: $now
        );
        $weekdayStats = WeekdayStats::create(
            activities: $allActivities,
            translator: $this->translator
        );
        $dayTimeStats = DaytimeStats::create($allActivities);

        $weeklyDistanceCharts = [];
        $distanceBreakdowns = [];
        $yearlyDistanceCharts = [];
        $yearlyStatistics = [];

        foreach ($activitiesPerActivityType as $activityType => $activities) {
            if ($activities->isEmpty()) {
                continue;
            }

            $activityType = ActivityType::from($activityType);
            if ($activityType->supportsWeeklyDistanceStats() && $chartData = WeeklyDistanceChart::create(
                activities: $activitiesPerActivityType[$activityType->value],
                unitSystem: $this->unitSystem,
                translator: $this->translator,
                now: $now,
            )->build()) {
                $weeklyDistanceCharts[$activityType->value] = Json::encode($chartData);
            }

            if ($activityType->supportsDistanceBreakdownStats()) {
                $distanceBreakdown = DistanceBreakdown::create(
                    activities: $activitiesPerActivityType[$activityType->value],
                    unitSystem: $this->unitSystem
                );

                if ($build = $distanceBreakdown->build()) {
                    $distanceBreakdowns[$activityType->value] = $build;
                }
            }

            if ($activityType->supportsYearlyStats()) {
                $yearlyDistanceCharts[$activityType->value] = Json::encode(
                    YearlyDistanceChart::create(
                        activities: $activitiesPerActivityType[$activityType->value],
                        unitSystem: $this->unitSystem,
                        translator: $this->translator,
                        now: $now
                    )->build()
                );

                $yearlyStatistics[$activityType->value] = YearlyStatistics::create(
                    activities: $activitiesPerActivityType[$activityType->value],
                    years: $allYears
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

        $activityTotals = ActivityTotals::create(
            activities: $allActivities,
            now: $now,
        );
        $trivia = Trivia::create($allActivities);
        $bestPowerOutputs = $this->activityPowerRepository->findBest();

        $this->filesystem->write(
            'build/html/dashboard.html',
            $this->twig->load('html/dashboard.html.twig')->render([
                'mostRecentActivities' => $allActivities->slice(0, 5),
                'intro' => $activityTotals,
                'weeklyDistanceCharts' => $weeklyDistanceCharts,
                'powerOutputs' => $bestPowerOutputs,
                'activityHeatmapChart' => Json::encode(
                    ActivityHeatmapChart::create(
                        activities: $allActivities,
                        activityIntensity: $this->activityIntensity,
                        translator: $this->translator,
                        now: $now,
                    )->build()
                ),
                'weekdayStatsChart' => Json::encode(
                    WeekdayStatsChart::create($weekdayStats)->build(),
                ),
                'weekdayStats' => $weekdayStats,
                'daytimeStatsChart' => Json::encode(
                    DaytimeStatsCharts::create(
                        daytimeStats: $dayTimeStats,
                        translator: $this->translator,
                    )->build(),
                ),
                'daytimeStats' => $dayTimeStats,
                'distanceBreakdowns' => $distanceBreakdowns,
                'trivia' => $trivia,
                'ftpHistoryChart' => !$allFtps->isEmpty() ? Json::encode(
                    FtpHistoryChart::create(
                        ftps: $allFtps,
                        now: $now
                    )->build()
                ) : null,
                'timeInHeartRateZoneChart' => Json::encode(
                    TimeInHeartRateZoneChart::create(
                        timeInSecondsInHeartRateZoneOne: $this->activityHeartRateRepository->findTotalTimeInSecondsInHeartRateZone(HeartRateZone::ONE),
                        timeInSecondsInHeartRateZoneTwo: $this->activityHeartRateRepository->findTotalTimeInSecondsInHeartRateZone(HeartRateZone::TWO),
                        timeInSecondsInHeartRateZoneThree: $this->activityHeartRateRepository->findTotalTimeInSecondsInHeartRateZone(HeartRateZone::THREE),
                        timeInSecondsInHeartRateZoneFour: $this->activityHeartRateRepository->findTotalTimeInSecondsInHeartRateZone(HeartRateZone::FOUR),
                        timeInSecondsInHeartRateZoneFive: $this->activityHeartRateRepository->findTotalTimeInSecondsInHeartRateZone(HeartRateZone::FIVE),
                        translator: $this->translator,
                    )->build(),
                ),
                'challengeConsistency' => ChallengeConsistency::create(
                    months: $allMonths,
                    activities: $allActivities
                ),
                'yearlyDistanceCharts' => $yearlyDistanceCharts,
                'yearlyStatistics' => $yearlyStatistics,
            ]),
        );

        if (!empty($bestPowerOutputs)) {
            $this->filesystem->write(
                'build/html/power-output.html',
                $this->twig->load('html/power-output.html.twig')->render([
                    'powerOutputChart' => Json::encode(
                        PowerOutputChart::create($bestPowerOutputs)->build()
                    ),
                ]),
            );
        }
    }
}
