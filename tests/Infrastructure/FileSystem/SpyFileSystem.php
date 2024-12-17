<?php

declare(strict_types=1);

namespace App\Tests\Infrastructure\FileSystem;

use League\Flysystem\DirectoryListing;
use League\Flysystem\FilesystemOperator;

final class SpyFileSystem implements FilesystemOperator
{
    private array $writes = [];

    public function fileExists(string $location): bool
    {
        return false;
    }

    public function directoryExists(string $location): bool
    {
        return true;
    }

    public function has(string $location): bool
    {
        return true;
    }

    public function read(string $location): string
    {
        return $location;
    }

    public function readStream(string $location): void
    {
    }

    public function listContents(string $location, bool $deep = self::LIST_SHALLOW): DirectoryListing
    {
        // TODO: Implement listContents() method.
    }

    public function lastModified(string $path): int
    {
        return 0;
    }

    public function fileSize(string $path): int
    {
        return 0;
    }

    public function mimeType(string $path): string
    {
        return 'mimetype';
    }

    public function visibility(string $path): string
    {
        return 'visible';
    }

    public function write(string $location, string $contents, array $config = []): void
    {
        $this->writes[$location] = $contents;
    }

    public function getWrites(): array
    {
        return $this->writes;
    }

    public function resetWrites(): void
    {
        $this->writes = [];
    }

    public function writeStream(string $location, $contents, array $config = []): void
    {
    }

    public function setVisibility(string $path, string $visibility): void
    {
    }

    public function delete(string $location): void
    {
    }

    public function deleteDirectory(string $location): void
    {
    }

    public function createDirectory(string $location, array $config = []): void
    {
    }

    public function move(string $source, string $destination, array $config = []): void
    {
    }

    public function copy(string $source, string $destination, array $config = []): void
    {
    }
}
