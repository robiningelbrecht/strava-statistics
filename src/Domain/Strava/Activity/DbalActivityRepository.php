<?php

namespace App\Domain\Strava\Activity;

use App\Domain\Nominatim\Location;
use App\Domain\Strava\Gear\GearId;
use App\Domain\Strava\Gear\GearIds;
use App\Infrastructure\Eventing\EventBus;
use App\Infrastructure\Exception\EntityNotFound;
use App\Infrastructure\Serialization\Json;
use App\Infrastructure\ValueObject\Time\SerializableDateTime;
use Doctrine\DBAL\Connection;

final class DbalActivityRepository implements ActivityRepository
{
    /** @var array<int|string, Activities> */
    public static array $cachedActivities = [];

    public function __construct(
        private readonly Connection $connection,
        private readonly EventBus $eventBus,
    ) {
    }

    public function add(Activity $activity): void
    {
        $sql = 'INSERT INTO Activity (activityId, startDateTime, data, weather, gearId, location)
        VALUES (:activityId, :startDateTime, :data, :weather, :gearId, :location)';

        $this->connection->executeStatement($sql, [
            'activityId' => $activity->getId(),
            'startDateTime' => $activity->getStartDate(),
            'data' => Json::encode($this->cleanData($activity->getData())),
            'weather' => Json::encode($activity->getAllWeatherData()),
            'gearId' => $activity->getGearId(),
            'location' => Json::encode($activity->getLocation()),
        ]);
    }

    public function update(Activity $activity): void
    {
        $sql = 'UPDATE Activity 
        SET data = :data, gearId = :gearId, location = :location
        WHERE activityId = :activityId';

        $this->connection->executeStatement($sql, [
            'activityId' => $activity->getId(),
            'data' => Json::encode($this->cleanData($activity->getData())),
            'gearId' => $activity->getGearId(),
            'location' => Json::encode($activity->getLocation()),
        ]);
    }

    public function delete(Activity $activity): void
    {
        $sql = 'DELETE FROM Activity 
        WHERE activityId = :activityId';

        $this->connection->executeStatement($sql, [
            'activityId' => $activity->getId(),
        ]);

        $this->eventBus->publishEvents($activity->getRecordedEvents());
    }

    /**
     * @param array<mixed> $data
     *
     * @return array<mixed>
     */
    private function cleanData(array $data): array
    {
        if (isset($data['map']['polyline'])) {
            unset($data['map']['polyline']);
        }
        if (isset($data['laps'])) {
            unset($data['laps']);
        }
        if (isset($data['splits_standard'])) {
            unset($data['splits_standard']);
        }
        if (isset($data['splits_metric'])) {
            unset($data['splits_metric']);
        }
        if (isset($data['stats_visibility'])) {
            unset($data['stats_visibility']);
        }

        return $data;
    }

    public function find(ActivityId $activityId): Activity
    {
        $queryBuilder = $this->connection->createQueryBuilder();
        $queryBuilder->select('*')
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
        if (array_key_exists($cacheKey, DbalActivityRepository::$cachedActivities)) {
            return DbalActivityRepository::$cachedActivities[$cacheKey];
        }

        $queryBuilder = $this->connection->createQueryBuilder();
        $queryBuilder->select('*')
            ->from('Activity')
            ->orderBy('startDateTime', 'DESC')
            ->setMaxResults($limit);

        $activities = array_map(
            fn (array $result) => $this->hydrate($result),
            $queryBuilder->executeQuery()->fetchAllAssociative()
        );
        DbalActivityRepository::$cachedActivities[$cacheKey] = Activities::fromArray($activities);

        return DbalActivityRepository::$cachedActivities[$cacheKey];
    }

    public function findActivityIds(): ActivityIds
    {
        $queryBuilder = $this->connection->createQueryBuilder();
        $queryBuilder->select('activityId')
            ->from('Activity')
            ->orderBy('startDateTime', 'DESC');

        return ActivityIds::fromArray(array_map(
            fn (string $id) => ActivityId::fromString($id),
            $queryBuilder->executeQuery()->fetchFirstColumn(),
        ));
    }

    public function findUniqueGearIds(): GearIds
    {
        $queryBuilder = $this->connection->createQueryBuilder();
        $queryBuilder->select('gearId')
            ->distinct()
            ->from('Activity')
            ->andWhere('gearId IS NOT NULL')
            ->orderBy('startDateTime', 'DESC');

        return GearIds::fromArray(array_map(
            fn (string $id) => GearId::fromString($id),
            $queryBuilder->executeQuery()->fetchFirstColumn(),
        ));
    }

    public function findMostRiddenState(): ?string
    {
        $queryBuilder = $this->connection->createQueryBuilder();
        $queryBuilder->select("JSON_EXTRACT(location, '$.state') as state")
            ->from('Activity')
            ->andWhere('state IS NOT NULL')
            ->groupBy("JSON_EXTRACT(location, '$.state')")
            ->orderBy('COUNT(*)', 'DESC');

        return $queryBuilder->executeQuery()->fetchOne();
    }

    /**
     * @param array<mixed> $result
     */
    private function hydrate(array $result): Activity
    {
        $location = Json::decode($result['location'] ?? '[]');

        return Activity::fromState(
            activityId: ActivityId::fromString($result['activityId']),
            startDateTime: SerializableDateTime::fromString($result['startDateTime']),
            data: Json::decode($result['data']),
            location: $location ? Location::fromState($location) : null,
            weather: Json::decode($result['weather'] ?? '[]'),
            gearId: GearId::fromOptionalString($result['gearId']),
        );
    }
}
