<?php

namespace App\Domain\Strava\Activity\ImportActivities;

use App\Domain\Measurement\Length\Meter;
use App\Domain\Nominatim\Nominatim;
use App\Domain\Strava\Activity\Activity;
use App\Domain\Strava\Activity\ActivityId;
use App\Domain\Strava\Activity\ActivityRepository;
use App\Domain\Strava\Activity\SportType\SportType;
use App\Domain\Strava\Activity\SportType\SportTypesToImport;
use App\Domain\Strava\Gear\GearId;
use App\Domain\Strava\Strava;
use App\Domain\Strava\StravaDataImportStatus;
use App\Domain\Weather\OpenMeteo\OpenMeteo;
use App\Infrastructure\CQRS\Bus\Command;
use App\Infrastructure\CQRS\Bus\CommandHandler;
use App\Infrastructure\Exception\EntityNotFound;
use App\Infrastructure\ValueObject\Geography\Coordinate;
use App\Infrastructure\ValueObject\Identifier\UuidFactory;
use App\Infrastructure\ValueObject\Time\SerializableDateTime;
use App\Infrastructure\ValueObject\Time\SerializableTimezone;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\RequestException;
use League\Flysystem\FilesystemOperator;

final readonly class ImportActivitiesCommandHandler implements CommandHandler
{
    public function __construct(
        private Strava $strava,
        private OpenMeteo $openMeteo,
        private Nominatim $nominatim,
        private ActivityRepository $activityRepository,
        private FilesystemOperator $filesystem,
        private SportTypesToImport $sportTypesToImport,
        private StravaDataImportStatus $stravaDataImportStatus,
        private UuidFactory $uuidFactory,
    ) {
    }

    public function handle(Command $command): void
    {
        assert($command instanceof ImportActivities);
        $command->getOutput()->writeln('Importing activities...');

        $allActivityIds = $this->activityRepository->findActivityIds();
        $activityIdsToDelete = array_combine(
            $allActivityIds->map(fn (ActivityId $activityId) => (string) $activityId),
            $allActivityIds->toArray(),
        );

        foreach ($this->strava->getActivities() as $stravaActivity) {
            if (!$sportType = SportType::tryFrom($stravaActivity['sport_type'])) {
                $command->getOutput()->writeln(sprintf(
                    '  => Sport type "%s" not supported yet. <a href="https://github.com/robiningelbrecht/strava-statistics/issues/new?assignees=robiningelbrecht&labels=new+feature&projects=&template=feature_request.md&title=Add+support+for+sport+type+%s>Open a new GitHub issue</a> to if you want support for this sport type',
                    $stravaActivity['sport_type'],
                    $stravaActivity['sport_type']));
                continue;
            }
            if (!$this->sportTypesToImport->has($sportType)) {
                continue;
            }

            $activityId = ActivityId::fromUnprefixed((string) $stravaActivity['id']);
            try {
                $activity = $this->activityRepository->find($activityId);
                $activity
                    ->updateName($stravaActivity['name'])
                    ->updateDescription($stravaActivity['description'] ?? '')
                    ->updateElevation(Meter::from($stravaActivity['total_elevation_gain']))
                    ->updateKudoCount($stravaActivity['kudos_count'] ?? 0)
                    ->updateGearId(GearId::fromOptionalUnprefixed($stravaActivity['gear_id'] ?? null));

                if (!$activity->getLocation() && $sportType->supportsReverseGeocoding()
                    && $activity->getLatitude() && $activity->getLongitude()) {
                    $reverseGeocodedAddress = $this->nominatim->reverseGeocode(Coordinate::createFromLatAndLng(
                        latitude: $activity->getLatitude(),
                        longitude: $activity->getLongitude(),
                    ));

                    $activity->updateLocation($reverseGeocodedAddress);
                }

                $this->activityRepository->update($activity);
                unset($activityIdsToDelete[(string) $activity->getId()]);
                $command->getOutput()->writeln(sprintf(
                    '  => Updated activity "%s - %s"',
                    $activity->getName(),
                    $activity->getStartDate()->format('d-m-Y'))
                );
            } catch (EntityNotFound) {
                try {
                    $startDate = SerializableDateTime::createFromFormat(
                        format: Activity::DATE_TIME_FORMAT,
                        datetime: $stravaActivity['start_date_local'],
                        timezone: SerializableTimezone::default(),
                    );
                    $activity = Activity::create(
                        activityId: $activityId,
                        startDateTime: $startDate,
                        sportType: $sportType,
                        data: $this->strava->getActivity($activityId),
                        gearId: GearId::fromOptionalUnprefixed($stravaActivity['gear_id'] ?? null)
                    );

                    $localImagePaths = [];

                    if ($activity->getTotalImageCount() > 0) {
                        $photos = $this->strava->getActivityPhotos($activity->getId());
                        foreach ($photos as $photo) {
                            if (empty($photo['urls'][5000])) {
                                continue;
                            }

                            /** @var string $urlPath */
                            $urlPath = parse_url((string) $photo['urls'][5000], PHP_URL_PATH);
                            $extension = pathinfo($urlPath, PATHINFO_EXTENSION);
                            $imagePath = sprintf('files/activities/%s.%s', $this->uuidFactory->random(), $extension);
                            $this->filesystem->write(
                                'storage/'.$imagePath,
                                $this->strava->downloadImage($photo['urls'][5000])
                            );
                            $localImagePaths[] = $imagePath;
                        }
                        $activity->updateLocalImagePaths($localImagePaths);
                    }

                    if ($sportType->supportsWeather() && $activity->getLatitude() && $activity->getLongitude()) {
                        $weather = $this->openMeteo->getWeatherStats(
                            $activity->getLatitude(),
                            $activity->getLongitude(),
                            $activity->getStartDate()
                        );
                        $activity->updateWeather($weather);
                    }

                    if ($sportType->supportsReverseGeocoding() && $activity->getLatitude() && $activity->getLongitude()) {
                        $reverseGeocodedAddress = $this->nominatim->reverseGeocode(Coordinate::createFromLatAndLng(
                            latitude: $activity->getLatitude(),
                            longitude: $activity->getLongitude(),
                        ));

                        $activity->updateLocation($reverseGeocodedAddress);
                    }

                    $this->activityRepository->add($activity);
                    unset($activityIdsToDelete[(string) $activity->getId()]);
                    $command->getOutput()->writeln(sprintf(
                        '  => Imported activity "%s - %s"',
                        $activity->getName(),
                        $activity->getStartDate()->format('d-m-Y'))
                    );
                } catch (ClientException|RequestException $exception) {
                    $this->stravaDataImportStatus->markActivityImportAsUncompleted();

                    if (!$exception->getResponse()) {
                        // Re-throw, we only want to catch supported error codes.
                        throw $exception;
                    }

                    if (429 === $exception->getResponse()->getStatusCode()) {
                        // This will allow initial imports with a lot of activities to proceed the next day.
                        // This occurs when we exceed Strava API rate limits or throws an unexpected error.
                        $command->getOutput()->writeln('<error>You probably reached Strava API rate limits. You will need to import the rest of your activities tomorrow</error>');

                        return;
                    }

                    $command->getOutput()->writeln(sprintf('<error>Strava API threw error: %s</error>', $exception->getMessage()));

                    return;
                }
            }
        }

        $this->stravaDataImportStatus->markActivityImportAsCompleted();
        if (empty($activityIdsToDelete)) {
            return;
        }

        foreach ($activityIdsToDelete as $activityId) {
            $activity = $this->activityRepository->find($activityId);
            $activity->delete();
            $this->activityRepository->delete($activity);

            $command->getOutput()->writeln(sprintf(
                '  => Deleted activity "%s - %s"',
                $activity->getName(),
                $activity->getStartDate()->format('d-m-Y'))
            );
        }
    }
}
