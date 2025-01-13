<?php

namespace App\Domain\Strava\Activity\Stream;

use App\Domain\Strava\Activity\ActivityId;
use App\Infrastructure\Exception\EntityNotFound;
use App\Infrastructure\Repository\DbalRepository;
use App\Infrastructure\Serialization\Json;
use App\Infrastructure\ValueObject\Time\SerializableDateTime;
use Doctrine\DBAL\ArrayParameterType;

final readonly class DbalActivityStreamRepository extends DbalRepository implements ActivityStreamRepository
{
    public function add(ActivityStream $stream): void
    {
        $sql = 'INSERT INTO ActivityStream (activityId, streamType, data, createdOn, bestAverages)
        VALUES (:activityId, :streamType, :data, :createdOn, :bestAverages)';

        $this->connection->executeStatement($sql, [
            'activityId' => $stream->getActivityId(),
            'streamType' => $stream->getStreamType()->value,
            'data' => Json::encode($stream->getData()),
            'createdOn' => $stream->getCreatedOn(),
            'bestAverages' => !empty($stream->getBestAverages()) ? Json::encode($stream->getBestAverages()) : null,
        ]);
    }

    public function update(ActivityStream $stream): void
    {
        $sql = 'UPDATE ActivityStream 
        SET bestAverages = :bestAverages
        WHERE activityId = :activityId
        AND streamType = :streamType';

        $this->connection->executeStatement($sql, [
            'activityId' => $stream->getActivityId(),
            'streamType' => $stream->getStreamType()->value,
            'bestAverages' => Json::encode($stream->getBestAverages()),
        ]);
    }

    public function delete(ActivityStream $stream): void
    {
        $sql = 'DELETE FROM ActivityStream
        WHERE activityId = :activityId
        AND streamType = :streamType';

        $this->connection->executeStatement($sql, [
            'activityId' => $stream->getActivityId(),
            'streamType' => $stream->getStreamType()->value,
        ]);
    }

    public function isImportedForActivity(ActivityId $activityId): bool
    {
        $queryBuilder = $this->connection->createQueryBuilder();
        $queryBuilder->select('*')
            ->from('ActivityStream')
            ->andWhere('activityId = :activityId')
            ->setParameter('activityId', $activityId);

        return !empty($queryBuilder->executeQuery()->fetchOne());
    }

    public function hasOneForActivityAndStreamType(ActivityId $activityId, StreamType $streamType): bool
    {
        $queryBuilder = $this->connection->createQueryBuilder();
        $queryBuilder->select('*')
            ->from('ActivityStream')
            ->andWhere('activityId = :activityId')
            ->setParameter('activityId', $activityId)
            ->andWhere('streamType = :streamType')
            ->setParameter('streamType', $streamType->value);

        return !empty($queryBuilder->executeQuery()->fetchOne());
    }

    public function findByStreamType(StreamType $streamType): ActivityStreams
    {
        $queryBuilder = $this->connection->createQueryBuilder();
        $queryBuilder->select('*')
            ->from('ActivityStream')
            ->andWhere('streamType = :streamType')
            ->setParameter('streamType', $streamType->value);

        return ActivityStreams::fromArray(array_map(
            fn (array $result) => $this->hydrate($result),
            $queryBuilder->executeQuery()->fetchAllAssociative()
        ));
    }

    public function findByActivityAndStreamTypes(ActivityId $activityId, StreamTypes $streamTypes): ActivityStreams
    {
        $queryBuilder = $this->connection->createQueryBuilder();
        $queryBuilder->select('*')
            ->from('ActivityStream')
            ->andWhere('activityId = :activityId')
            ->setParameter('activityId', $activityId)
            ->andWhere('streamType IN (:streamTypes)')
            ->setParameter('streamTypes', array_map(
                fn (StreamType $streamType) => $streamType->value,
                $streamTypes->toArray()
            ), ArrayParameterType::STRING);

        return ActivityStreams::fromArray(array_map(
            fn (array $result) => $this->hydrate($result),
            $queryBuilder->executeQuery()->fetchAllAssociative()
        ));
    }

    public function findByActivityId(ActivityId $activityId): ActivityStreams
    {
        $queryBuilder = $this->connection->createQueryBuilder();
        $queryBuilder->select('*')
            ->from('ActivityStream')
            ->andWhere('activityId = :activityId')
            ->setParameter('activityId', $activityId);

        return ActivityStreams::fromArray(array_map(
            fn (array $result) => $this->hydrate($result),
            $queryBuilder->executeQuery()->fetchAllAssociative()
        ));
    }

    public function findWithoutBestAverages(int $limit): ActivityStreams
    {
        $queryBuilder = $this->connection->createQueryBuilder();
        $queryBuilder->select('*')
            ->from('ActivityStream')
            ->andWhere('bestAverages IS NULL')
            ->orderBy('activityId')
            ->setMaxResults(100);

        return ActivityStreams::fromArray(array_map(
            fn (array $result) => $this->hydrate($result),
            $queryBuilder->executeQuery()->fetchAllAssociative()
        ));
    }

    public function findWithBestAverageFor(int $intervalInSeconds, StreamType $streamType): ActivityStream
    {
        $queryBuilder = $this->connection->createQueryBuilder();
        $queryBuilder->select('*')
            ->from('ActivityStream')
            ->andWhere('streamType = :streamType')
            ->setParameter('streamType', $streamType->value)
            ->andWhere('JSON_EXTRACT(bestAverages, "$.'.$intervalInSeconds.'") IS NOT NULL')
            ->orderBy('JSON_EXTRACT(bestAverages, "$.'.$intervalInSeconds.'")', 'DESC')
            ->addOrderBy('createdOn', 'DESC')
            ->setMaxResults(1);

        if (!$result = $queryBuilder->fetchAssociative()) {
            throw new EntityNotFound('ActivityStream for average not found');
        }

        return $this->hydrate($result);
    }

    /**
     * @param array<mixed> $result
     */
    private function hydrate(array $result): ActivityStream
    {
        return ActivityStream::fromState(
            activityId: ActivityId::fromString($result['activityId']),
            streamType: StreamType::from($result['streamType']),
            streamData: Json::decode($result['data']),
            createdOn: SerializableDateTime::fromString($result['createdOn']),
            bestAverages: Json::decode($result['bestAverages'] ?? '[]'),
        );
    }
}
