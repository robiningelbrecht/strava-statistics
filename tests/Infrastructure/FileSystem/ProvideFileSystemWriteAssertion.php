<?php

declare(strict_types=1);

namespace App\Tests\Infrastructure\FileSystem;

use Spatie\Snapshots\MatchesSnapshots;

trait ProvideFileSystemWriteAssertion
{
    use MatchesSnapshots;

    public function assertFileSystemWrites(array $writes): void
    {
        foreach ($writes as $location => $content) {
            if (str_ends_with($location, '.json')) {
                $this->assertMatchesJsonSnapshot($content);
                continue;
            }
            $this->assertMatchesHtmlSnapshot($content);
        }
    }
}
