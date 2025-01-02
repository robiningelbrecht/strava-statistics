<?php

namespace App\Domain\Strava\Activity\Stream\ImportActivityStreams;

use App\Domain\Strava\Activity\ActivityRepository;
use App\Domain\Strava\Activity\Stream\ActivityStream;
use App\Domain\Strava\Activity\Stream\ActivityStreamRepository;
use App\Domain\Strava\Activity\Stream\StreamType;
use App\Domain\Strava\Strava;
use App\Domain\Strava\StravaErrorStatusCode;
use App\Infrastructure\CQRS\Bus\Command;
use App\Infrastructure\CQRS\Bus\CommandHandler;
use App\Infrastructure\Time\Sleep;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\RequestException;

final readonly class ImportActivityStreamsCommandHandler implements CommandHandler
{
    public function __construct(
        private Strava $strava,
        private ActivityRepository $activityRepository,
        private ActivityStreamRepository $activityStreamRepository,
        private Sleep $sleep,
    ) {
    }

    public function handle(Command $command): void
    {
        assert($command instanceof ImportActivityStreams);
        $command->getOutput()->writeln('Importing activity streams...');

        foreach ($this->activityRepository->findActivityIds() as $activityId) {
            if ($this->activityStreamRepository->isImportedForActivity($activityId)) {
                // Streams for this activity have been imported already, skip.
                continue;
            }
            $stravaStreams = [];
            try {
                $stravaStreams = $this->strava->getAllActivityStreams($activityId);
            } catch (ClientException|RequestException $exception) {
                if (!$exception->getResponse() || !in_array($exception->getResponse()->getStatusCode(), [404, ...array_map(
                    fn (StravaErrorStatusCode $errorStatusCode) => $errorStatusCode->value,
                    StravaErrorStatusCode::cases(),
                )])) {
                    // Re-throw, we only want to catch supported error codes.
                    throw $exception;
                }

                $stravaErrorStatusCode = StravaErrorStatusCode::tryFrom(
                    $exception->getResponse()->getStatusCode()
                );
                if ($stravaErrorStatusCode) {
                    // This will allow initial imports with a lot of activities to proceed the next day.
                    // This occurs when we exceed Strava API rate limits or throws an unexpected error.
                    $command->getOutput()->writeln(sprintf('<error>%s</error>', $stravaErrorStatusCode->getErrorMessage($exception)));
                    break;
                }

                if (404 === $exception->getResponse()->getStatusCode()) {
                    continue;
                }
            }

            $stravaStreams = array_filter(
                $stravaStreams,
                fn (array $stravaStream): bool => !is_null(StreamType::tryFrom($stravaStream['type']))
            );
            if (empty($stravaStreams)) {
                // We need this hack for activities that do not have streams.
                // This way we can "tag" them as imported.
                $stravaStreams[] = [
                    'type' => StreamType::HACK->value,
                    'data' => [],
                ];
            }

            $activity = $this->activityRepository->find($activityId);
            foreach ($stravaStreams as $stravaStream) {
                if (!$streamType = StreamType::tryFrom($stravaStream['type'])) {
                    continue;
                }

                $stream = ActivityStream::create(
                    activityId: $activityId,
                    streamType: $streamType,
                    streamData: $stravaStream['data'],
                    createdOn: $activity->getStartDate(),
                );
                $this->activityStreamRepository->add($stream);
                $command->getOutput()->writeln(sprintf('  => Imported activity stream "%s"', $stream->getName()));
            }

            // Try to avoid Strava rate limits.
            $this->sleep->sweetDreams(10);
        }
    }
}
