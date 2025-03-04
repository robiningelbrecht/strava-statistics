<?php

namespace App\Tests\Domain\App\BuildBadgeSvg;

use App\Domain\App\BuildBadgeSvg\BuildBadgeSvg;
use App\Infrastructure\ValueObject\Time\SerializableDateTime;
use App\Tests\Domain\App\BuildAppFilesTestCase;

class BuildBadgeSvgCommandHandlerTest extends BuildAppFilesTestCase
{
    public function testHandle(): void
    {
        $this->provideFullTestSet();

        $this->commandBus->dispatch(new BuildBadgeSvg(SerializableDateTime::fromString('2023-10-17 16:15:04')));

        $fileSystems = [
            $this->getContainer()->get('build.storage'),
            $this->getContainer()->get('file.storage'),
        ];

        foreach ($fileSystems as $fileSystem) {
            $this->assertFileSystemWrites($fileSystem);
        }
    }
}
