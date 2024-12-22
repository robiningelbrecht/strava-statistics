<?php

declare(strict_types=1);

namespace App\Tests\Domain\GitHub;

use App\Domain\GitHub\GitHub;

final readonly class FakeGitHub implements GitHub
{
    public function getRepoLatestRelease(string $fullRepoName): string
    {
        return 'v0.1.8';
    }
}
