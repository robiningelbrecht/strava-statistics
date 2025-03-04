<?php

namespace App\Tests\Domain\App\BuildHeatmapHtml;

use App\Domain\App\BuildHeatmapHtml\BuildHeatmapHtml;
use App\Infrastructure\ValueObject\Time\SerializableDateTime;
use App\Tests\Domain\App\BuildAppFilesTestCase;

class BuildHeatmapHtmlCommandHandlerTest extends BuildAppFilesTestCase
{
    public function testHandle(): void
    {
        $this->provideFullTestSet();

        $this->commandBus->dispatch(new BuildHeatmapHtml(SerializableDateTime::fromString('2023-10-17 16:15:04')));
        $this->assertFileSystemWrites($this->getContainer()->get('build.storage'));
    }
}
