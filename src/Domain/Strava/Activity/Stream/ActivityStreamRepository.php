<?php

namespace App\Domain\Strava\Activity\Stream;

use App\Domain\Strava\Activity\ActivityId;

interface ActivityStreamRepository
{
    public function add(ActivityStream $stream): void;

    public function update(ActivityStream $stream): void;

    public function delete(ActivityStream $stream): void;

    public function isImportedForActivity(ActivityId $activityId): bool;

    public function hasOneForActivityAndStreamType(ActivityId $activityId, StreamType $streamType): bool;

    public function findByStreamType(StreamType $streamType): ActivityStreams;

    public function findByActivityAndStreamTypes(ActivityId $activityId, StreamTypes $streamTypes): ActivityStreams;

    public function findByActivityId(ActivityId $activityId): ActivityStreams;

    public function findWithoutBestAverages(int $limit): ActivityStreams;

    public function findWithBestAverageFor(int $intervalInSeconds, StreamType $streamType): ActivityStream;
}
