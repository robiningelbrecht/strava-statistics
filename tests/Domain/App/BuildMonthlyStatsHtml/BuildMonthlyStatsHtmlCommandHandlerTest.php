<?php

namespace App\Tests\Domain\App\BuildMonthlyStatsHtml;

use App\Domain\App\BuildMonthlyStatsHtml\BuildMonthlyStatsHtml;
use App\Infrastructure\ValueObject\Time\SerializableDateTime;
use App\Tests\Domain\App\BuildAppFilesTestCase;

class BuildMonthlyStatsHtmlCommandHandlerTest extends BuildAppFilesTestCase
{
    public function testHandle(): void
    {
        $this->provideFullTestSet();

        $this->commandBus->dispatch(new BuildMonthlyStatsHtml(SerializableDateTime::fromString('2023-10-17 16:15:04')));
        $this->assertFileSystemWrites($this->getContainer()->get('build.storage'));
    }
}
