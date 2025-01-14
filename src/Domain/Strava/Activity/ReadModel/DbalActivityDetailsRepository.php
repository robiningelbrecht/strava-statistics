<?php

declare(strict_types=1);

namespace App\Domain\Strava\Activity\ReadModel;

use App\Domain\Measurement\Length\Kilometer;
use App\Domain\Measurement\Length\Meter;
use App\Domain\Measurement\Velocity\KmPerHour;
use App\Domain\Strava\Activity\ActivityId;
use App\Domain\Strava\Activity\SportType\SportType;
use App\Domain\Strava\Gear\GearId;
use App\Infrastructure\Exception\EntityNotFound;
use App\Infrastructure\Geocoding\Nominatim\Location;
use App\Infrastructure\Serialization\Json;
use App\Infrastructure\ValueObject\Geography\Latitude;
use App\Infrastructure\ValueObject\Geography\Longitude;
use App\Infrastructure\ValueObject\String\Name;
use App\Infrastructure\ValueObject\Time\SerializableDateTime;
use Doctrine\DBAL\Connection;

final class DbalActivityDetailsRepository implements ActivityDetailsRepository
{
    /** @var array<int|string, Activities> */
    public static array $cachedActivities = [];

    public function __construct(
        private readonly Connection $connection,
    ) {
    }

    private function buildSqlSelectStatement(): string
    {
        $fields = [
            'activityId',
            'startDateTime',
            'sportType',
            'location',
            'weather',
            'gearId',
            'JSON_EXTRACT(data, "$.name") as name',
            'JSON_EXTRACT(data, "$.description") as description',
            'JSON_EXTRACT(data, "$.distance") as distance',
            'JSON_EXTRACT(data, "$.total_elevation_gain") as elevation',
            'JSON_EXTRACT(data, "$.start_latlng[0]") as latitude',
            'JSON_EXTRACT(data, "$.start_latlng[1]") as longitude',
            'JSON_EXTRACT(data, "$.kudos_count") as kudoCount',
            'JSON_EXTRACT(data, "$.localImagePaths") as localImagePaths',
            'JSON_EXTRACT(data, "$.total_photo_count") as totalPhotoCount',
            'JSON_EXTRACT(data, "$.calories") as calories',
            'JSON_EXTRACT(data, "$.average_watts") as averageWatts',
            'JSON_EXTRACT(data, "$.max_watts") as maxWatts',
            'JSON_EXTRACT(data, "$.average_speed") as averageSpeed',
            'JSON_EXTRACT(data, "$.max_speed") as maxSpeed',
            'JSON_EXTRACT(data, "$.average_heartrate") as averageHeartRate',
            'JSON_EXTRACT(data, "$.max_heartrate") as maxHeartRate',
            'JSON_EXTRACT(data, "$.average_cadence") as averageCadence',
            'JSON_EXTRACT(data, "$.moving_time") as movingTimeInSeconds',
            'JSON_EXTRACT(data, "$.map.summary_polyline") as polyline',
            'JSON_EXTRACT(data, "$.device_name") as deviceName',
            'JSON_EXTRACT(data, "$.segment_efforts") as segmentEfforts',
        ];

        return implode(', ', $fields);
    }

    public function find(ActivityId $activityId): ActivityDetails
    {
        $queryBuilder = $this->connection->createQueryBuilder();
        $queryBuilder->select($this->buildSqlSelectStatement())
            ->from('Activity')
            ->andWhere('activityId = :activityId')
            ->setParameter('activityId', $activityId);

        if (!$result = $queryBuilder->executeQuery()->fetchAssociative()) {
            throw new EntityNotFound(sprintf('Activity "%s" not found', $activityId));
        }

        return $this->hydrate($result);
    }

    public function findAll(?int $limit = null): Activities
    {
        $cacheKey = $limit ?? 'all';
        if (array_key_exists($cacheKey, DbalActivityDetailsRepository::$cachedActivities)) {
            return DbalActivityDetailsRepository::$cachedActivities[$cacheKey];
        }

        $queryBuilder = $this->connection->createQueryBuilder();
        $queryBuilder->select($this->buildSqlSelectStatement())
            ->from('Activity')
            ->orderBy('startDateTime', 'DESC')
            ->setMaxResults($limit);

        $activities = array_map(
            fn (array $result) => $this->hydrate($result),
            $queryBuilder->executeQuery()->fetchAllAssociative()
        );
        DbalActivityDetailsRepository::$cachedActivities[$cacheKey] = Activities::fromArray($activities);

        return DbalActivityDetailsRepository::$cachedActivities[$cacheKey];
    }

    public function findMostActiveState(): ?string
    {
        $queryBuilder = $this->connection->createQueryBuilder();
        $queryBuilder->select("JSON_EXTRACT(location, '$.state') as state")
            ->from('Activity')
            ->andWhere('state IS NOT NULL')
            ->groupBy("JSON_EXTRACT(location, '$.state')")
            ->orderBy('COUNT(*)', 'DESC');

        return ((string) $queryBuilder->executeQuery()->fetchOne()) ?: null;
    }

    /**
     * @param array<string, mixed> $result
     */
    private function hydrate(array $result): ActivityDetails
    {
        $location = Json::decode($result['location'] ?? '[]');

        return new ActivityDetails(
            activityId: ActivityId::fromString($result['activityId']),
            startDateTime: SerializableDateTime::fromString($result['startDateTime']),
            sportType: SportType::from($result['sportType']),
            name: Name::fromString($result['name']),
            description: $result['description'] ?: '',
            distance: Kilometer::from($result['distance'] / 1000),
            elevation: Meter::from($result['elevation'] ?: 0),
            latitude: Latitude::fromOptionalString((string) $result['latitude']),
            longitude: Longitude::fromOptionalString((string) $result['longitude']),
            calories: (int) ($result['calories'] ?? 0),
            averagePower: ((int) $result['averageWatts']) ?: null,
            maxPower: ((int) $result['maxWatts']) ?: null,
            averageSpeed: KmPerHour::from($result['averageSpeed'] * 3.6),
            maxSpeed: KmPerHour::from($result['maxSpeed'] * 3.6),
            averageHeartRate: ((int) $result['averageHeartRate']) ?: null,
            maxHeartRate: ((int) $result['maxHeartRate']) ?: null,
            averageCadence: ((int) $result['averageCadence']) ?: null,
            movingTimeInSeconds: $result['movingTimeInSeconds'] ?: 0,
            kudoCount: $result['kudoCount'] ?: 0,
            totalImageCount: $result['totalPhotoCount'] ?: 0,
            deviceName: $result['deviceName'],
            localImagePaths: !empty($result['localImagePaths']) ? Json::decode($result['localImagePaths']) : [],
            polyline: $result['polyline'],
            location: $location ? Location::fromState($location) : null,
            segmentEfforts: $result['segmentEfforts'] ?: '[]',
            weather: $result['weather'] ?? '[]',
            gearId: GearId::fromOptionalString($result['gearId']),
        );
    }
}
