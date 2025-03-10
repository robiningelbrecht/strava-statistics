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
        // >how did you feel about the product when it arrived?
        // It was nice and the packaging was good, I was excited because ...
        //
        // >how did you feel about the product after a week using it?
        // I'm not completely satisfied as it turns out that ...
        $document = new Document();
        $document->content = $this->activity->getName();
        $document->sourceName = $this->activity->getName();
        $document->sourceType = 'activity';
        $document->hash = \hash('sha256', $document->content);

        return $document;
    }
}
