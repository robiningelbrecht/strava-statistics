<?php

declare(strict_types=1);

namespace App\Tests\Infrastructure\FileSystem;

use League\Flysystem\FileAttributes;
use League\Flysystem\FilesystemOperator;
use PHPUnit\Framework\Assert;
use Spatie\Snapshots\MatchesSnapshots;

trait provideAssertFileSystem
{
    use MatchesSnapshots;

    private string $snapshotName;

    protected function assertFileSystemWritesAreEmpty(FilesystemOperator $fileSystem): void
    {
        foreach ($fileSystem->listContents('/', true) as $item) {
            Assert::fail('fileSystem is ot empty');
        }

        Assert::assertTrue(true, 'fileSystem is empty');
    }

    protected function assertFileSystemWrites(FilesystemOperator $fileSystem): void
    {
        foreach ($fileSystem->listContents('/', true) as $item) {
            $path = $item->path();

            if (!$item instanceof FileAttributes) {
                continue;
            }

            $this->snapshotName = preg_replace('/[^a-zA-Z0-9]/', '-', $path);
            $content = $fileSystem->read($path);
            if (str_ends_with($path, '.json')) {
                $this->assertMatchesJsonSnapshot($content);
                continue;
            }
            if (str_ends_with($path, '.html')) {
                $this->assertMatchesHtmlSnapshot($content);
                continue;
            }
            if (str_ends_with($path, '.gpx') || str_ends_with($path, '.svg')) {
                $this->assertMatchesXmlSnapshot($content);
                continue;
            }
            $this->assertMatchesTextSnapshot($content);
        }
    }
}
