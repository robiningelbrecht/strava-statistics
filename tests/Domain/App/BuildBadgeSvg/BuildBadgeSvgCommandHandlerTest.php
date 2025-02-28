<?php

namespace App\Tests\Domain\App\BuildBadgeSvg;

use App\Domain\App\BuildBadgeSvg\BuildBadgeSvg;
use App\Infrastructure\CQRS\DomainCommand;
use App\Infrastructure\ValueObject\Time\SerializableDateTime;
use App\Tests\Domain\App\BuildAppFilesTestCase;
use League\Flysystem\FilesystemOperator;

class BuildBadgeSvgCommandHandlerTest extends BuildAppFilesTestCase
{
    /**
     * @return FilesystemOperator[]
     */
    protected function getFileSystemOperators(): array
    {
        return [
            $this->getContainer()->get('build.storage'),
            $this->getContainer()->get('file.storage'),
        ];
    }

    protected function getDomainCommand(): DomainCommand
    {
        return new BuildBadgeSvg(SerializableDateTime::fromString('2023-10-17 16:15:04'));
    }
}
