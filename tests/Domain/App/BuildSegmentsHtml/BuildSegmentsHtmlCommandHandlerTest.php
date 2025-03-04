<?php

namespace App\Tests\Domain\App\BuildSegmentsHtml;

use App\Domain\App\BuildSegmentsHtml\BuildSegmentsHtml;
use App\Infrastructure\ValueObject\Time\SerializableDateTime;
use App\Tests\Domain\App\BuildAppFilesTestCase;

class BuildSegmentsHtmlCommandHandlerTest extends BuildAppFilesTestCase
{
    public function testHandle(): void
    {
        $this->provideFullTestSet();

        $this->commandBus->dispatch(new BuildSegmentsHtml(SerializableDateTime::fromString('2023-10-17 16:15:04')));
        $this->assertFileSystemWrites($this->getContainer()->get('build.storage'));
    }
}
