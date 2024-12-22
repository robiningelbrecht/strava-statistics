<?php

declare(strict_types=1);

namespace App\Domain\GitHub;

interface GitHub
{
    public function getRepoLatestRelease(
        string $fullRepoName,
    ): string;
}
