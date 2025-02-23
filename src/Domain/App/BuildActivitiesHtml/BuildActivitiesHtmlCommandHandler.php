<?php

declare(strict_types=1);

namespace App\Domain\App\BuildActivitiesHtml;

use App\Domain\Strava\Activity\ActivitiesEnricher;
use App\Domain\Strava\Activity\ActivityTotals;
use App\Domain\Strava\Activity\HeartRateChart;
use App\Domain\Strava\Activity\HeartRateDistributionChart;
use App\Domain\Strava\Activity\PowerDistributionChart;
use App\Domain\Strava\Activity\Split\ActivitySplitRepository;
use App\Domain\Strava\Activity\SportType\SportTypeRepository;
use App\Domain\Strava\Activity\Stream\ActivityHeartRateRepository;
use App\Domain\Strava\Activity\Stream\ActivityPowerRepository;
use App\Domain\Strava\Activity\Stream\ActivityStreamRepository;
use App\Domain\Strava\Activity\Stream\StreamType;
use App\Domain\Strava\Athlete\AthleteRepository;
use App\Domain\Strava\Segment\SegmentEffort\SegmentEffortRepository;
use App\Infrastructure\CQRS\Bus\Command;
use App\Infrastructure\CQRS\Bus\CommandHandler;
use App\Infrastructure\Exception\EntityNotFound;
use App\Infrastructure\Serialization\Json;
use App\Infrastructure\ValueObject\DataTableRow;
use App\Infrastructure\ValueObject\Measurement\UnitSystem;
use League\Flysystem\FilesystemOperator;
use Twig\Environment;

final readonly class BuildActivitiesHtmlCommandHandler implements CommandHandler
{
    public function __construct(
        private AthleteRepository $athleteRepository,
        private ActivityPowerRepository $activityPowerRepository,
        private ActivityStreamRepository $activityStreamRepository,
        private ActivitySplitRepository $activitySplitRepository,
        private ActivityHeartRateRepository $activityHeartRateRepository,
        private SportTypeRepository $sportTypeRepository,
        private SegmentEffortRepository $segmentEffortRepository,
        private ActivitiesEnricher $activitiesEnricher,
        private UnitSystem $unitSystem,
        private Environment $twig,
        private FilesystemOperator $filesystem,
    ) {
    }

    public function handle(Command $command): void
    {
        assert($command instanceof BuildActivitiesHtml);

        $now = $command->getCurrentDateTime();
        $athlete = $this->athleteRepository->find();
        $importedSportTypes = $this->sportTypeRepository->findAll();
        $activities = $this->activitiesEnricher->getEnrichedActivities();

        $activityTotals = ActivityTotals::create(
            activities: $activities,
            now: $now,
        );

        $this->filesystem->write(
            'build/html/activities.html',
            $this->twig->load('html/activity/activities.html.twig')->render([
                'sportTypes' => $importedSportTypes,
                'activityTotals' => $activityTotals,
            ]),
        );

        $dataDatableRows = [];
        foreach ($activities as $activity) {
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
                    'timeIntervals' => ActivityPowerRepository::TIME_INTERVALS_IN_SECONDS_REDACTED,
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
    }
}
