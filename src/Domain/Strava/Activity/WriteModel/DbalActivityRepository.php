<?php

namespace App\Domain\Strava\Activity\WriteModel;

use App\Domain\Strava\Activity\ActivityId;
use App\Domain\Strava\Activity\ActivityIds;
use App\Domain\Strava\Activity\SportType\SportType;
use App\Domain\Strava\Gear\GearId;
use App\Domain\Strava\Gear\GearIds;
use App\Infrastructure\Eventing\EventBus;
use App\Infrastructure\Exception\EntityNotFound;
use App\Infrastructure\Geocoding\Nominatim\Location;
use App\Infrastructure\Repository\DbalRepository;
use App\Infrastructure\Serialization\Json;
use App\Infrastructure\ValueObject\Time\SerializableDateTime;
use Doctrine\DBAL\Connection;

final readonly class DbalActivityRepository extends DbalRepository implements ActivityRepository
{
    public function __construct(
        Connection $connection,
        private EventBus $eventBus,
    ) {
        parent::__construct($connection);
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

    public function add(Activity $activity): void
    {
        $sql = 'INSERT INTO Activity (activityId, startDateTime, sportType, data, weather, gearId, location)
        VALUES (:activityId, :startDateTime, :sportType, :data, :weather, :gearId, :location)';

        $this->connection->executeStatement($sql, [
            'activityId' => $activity->getId(),
            'startDateTime' => $activity->getStartDate(),
            'sportType' => $activity->getSportType()->value,
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

        return $data;
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

    /**
     * @param array<string, mixed> $result
     */
    private function hydrate(array $result): Activity
    {
        $location = Json::decode($result['location'] ?? '[]');

        return Activity::fromState(
            activityId: ActivityId::fromString($result['activityId']),
            startDateTime: SerializableDateTime::fromString($result['startDateTime']),
            sportType: SportType::from($result['sportType']),
            data: Json::decode($result['data']),
            location: $location ? Location::fromState($location) : null,
            weather: Json::decode($result['weather'] ?? '[]'),
            gearId: GearId::fromOptionalString($result['gearId']),
        );
    }
}
