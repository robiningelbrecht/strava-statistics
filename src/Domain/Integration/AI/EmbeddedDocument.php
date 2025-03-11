<?php

declare(strict_types=1);

namespace App\Domain\Integration\AI;

use App\Domain\Strava\Activity\Activity;
use LLPhant\Embeddings\Document;

final readonly class EmbeddedDocument
{
    public function __construct(
        private Activity $activity,
    ) {
    }

    public function build(): Document
    {
        $content = [
            sprintf('The name of this activity is %s and it took place on %s.', $this->activity->getName(), $this->activity->getStartDate()->format('d-m-Y')),
            sprintf('This activity was a %s.', $this->activity->getSportType()->value),
            'The workout had an intensity of 93 which we consider quite high.',
            sprintf('The activity took %s to complete', $this->activity->getMovingTimeFormatted()),
        ];
        $document = new Document();
        $document->content = implode(' ', $content);
        $document->sourceName = $this->activity->getName();
        $document->sourceType = $this->activity->getSportType()->getActivityType()->value;
        $document->hash = \hash('sha256', $document->content);

        return $document;
    }
}
