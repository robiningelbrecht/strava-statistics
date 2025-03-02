<?php

namespace App\Tests\Domain\App\BuildDashboardHtml;

use App\Domain\App\BuildDashboardHtml\BuildDashboardHtml;
use App\Infrastructure\ValueObject\Time\SerializableDateTime;
use App\Tests\Domain\App\BuildAppFilesTestCase;

class BuildDashboardHtmlCommandHandlerTest extends BuildAppFilesTestCase
{
    public function testHandle(): void
    {
        $this->provideFullTestSet();

        $this->commandBus->dispatch(new BuildDashboardHtml(SerializableDateTime::fromString('2023-10-17 16:15:04')));
        $this->assertFileSystemWrites($this->getContainer()->get('build.storage'));
    }

    public function testHandleForRunningActivitiesOnly(): void
    {
        $this->provideRunningOnlyTestSet();

        $this->commandBus->dispatch(new BuildDashboardHtml(SerializableDateTime::fromString('2023-10-17 16:15:04')));
        $this->assertFileSystemWrites($this->getContainer()->get('build.storage'));
    }
}
