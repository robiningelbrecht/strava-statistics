<?php

declare(strict_types=1);

namespace App\Domain\App\BuildIndexHtml;

use App\Infrastructure\CQRS\Bus\Command;
use App\Infrastructure\CQRS\Bus\CommandHandler;

final readonly class BuildIndexHtmlCommandHandler implements CommandHandler
{
    public function handle(Command $command): void
    {
        assert($command instanceof BuildIndexHtml);

        /*$this->filesystem->write(
            'build/html/index.html',
            $this->twig->load('html/index.html.twig')->render([
                'totalActivityCount' => count($allActivities),
                'eddingtons' => $eddingtonPerActivityType,
                'completedChallenges' => count($allChallenges),
                'totalPhotoCount' => count($allImages),
                'lastUpdate' => $now,
                'athlete' => $athlete,
            ]),
        );*/
    }
}
