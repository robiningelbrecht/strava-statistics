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
use App\Domain\Strava\Activity\Stream\BestPowerOutputs;
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
use App\Domain\Strava\EFtp\EFtpCalculator;
use App\Domain\Strava\EFtp\EFtpHistoryChart;
use App\Domain\Strava\Ftp\FtpHistoryChart;
use App\Domain\Strava\Ftp\FtpRepository;
use App\Domain\Strava\Trivia;
use App\Infrastructure\CQRS\Command;
use App\Infrastructure\CQRS\CommandHandler;
use App\Infrastructure\Exception\EntityNotFound;
use App\Infrastructure\Serialization\Json;
use App\Infrastructure\ValueObject\Measurement\UnitSystem;
use App\Infrastructure\ValueObject\Time\DateRange;
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
        private FilesystemOperator $buildStorage,
        private TranslatorInterface $translator,
        private EFtpCalculator $eftpCalculator,
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

        $this->eftpCalculator->enrichWithActivities($allActivities);

        $weeklyDistanceCharts = [];
        $distanceBreakdowns = [];
        $yearlyDistanceCharts = [];
        $yearlyStatistics = [];
        $eftpCharts = [];

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

            if ($chartData = EFtpHistoryChart::create(
                eftpCalculator: $this->eftpCalculator,
                activityType: $activityType,
                now: $now,
            )->build()) {
                $eftpCharts[$activityType->value] = Json::encode($chartData);
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

        $activityTotals = ActivityTotals::getInstance(
            activities: $allActivities,
            now: $now,
        );
        $trivia = Trivia::getInstance($allActivities);
        $bestAllTimePowerOutputs = $this->activityPowerRepository->findBestForActivityType(ActivityType::RIDE);

        $this->buildStorage->write(
            'dashboard.html',
            $this->twig->load('html/dashboard.html.twig')->render([
                'timeIntervals' => ActivityPowerRepository::TIME_INTERVALS_IN_SECONDS_REDACTED,
                'mostRecentActivities' => $allActivities->slice(0, 5),
                'intro' => $activityTotals,
                'weeklyDistanceCharts' => $weeklyDistanceCharts,
                'powerOutputs' => $bestAllTimePowerOutputs,
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
                'eftpHistoryCharts' => !empty($eftpCharts) ? $eftpCharts : null,
                'eftpNumberOfMonths' => $this->eftpCalculator->getNumberOfMonths(),
                'eftpFactors' => EFtpCalculator::EFTP_FACTORS,
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

        if ($bestAllTimePowerOutputs->isEmpty()) {
            return;
        }

        $bestPowerOutputs = BestPowerOutputs::empty();
        $bestPowerOutputs->add(
            description: $this->translator->trans('All time'),
            powerOutputs: $bestAllTimePowerOutputs
        );
        $bestPowerOutputs->add(
            description: $this->translator->trans('Last 45 days'),
            powerOutputs: $this->activityPowerRepository->findBestForActivityTypeInDateRange(
                activityType: ActivityType::RIDE,
                dateRange: DateRange::lastXDays($now, 45)
            )
        );
        $bestPowerOutputs->add(
            description: $this->translator->trans('Last 90 days'),
            powerOutputs: $this->activityPowerRepository->findBestForActivityTypeInDateRange(
                activityType: ActivityType::RIDE,
                dateRange: DateRange::lastXDays($now, 90)
            )
        );
        foreach ($allYears->reverse() as $year) {
            $bestPowerOutputs->add(
                description: (string) $year,
                powerOutputs: $this->activityPowerRepository->findBestForActivityTypeInDateRange(
                    activityType: ActivityType::RIDE,
                    dateRange: $year->getRange(),
                )
            );
        }

        $this->buildStorage->write(
            'power-output.html',
            $this->twig->load('html/power-output.html.twig')->render([
                'powerOutputChart' => Json::encode(
                    PowerOutputChart::create($bestPowerOutputs)->build()
                ),
                'bestPowerOutputs' => $bestPowerOutputs,
            ]),
        );
    }
}
