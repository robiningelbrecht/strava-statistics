<?php

namespace App\Tests\Domain\App\BuildPhotosHtml;

use App\Domain\App\BuildPhotosHtml\BuildPhotosHtml;
use App\Tests\Domain\App\BuildAppFilesTestCase;

class BuildPhotosHtmlCommandHandlerTest extends BuildAppFilesTestCase
{
    public function testHandle(): void
    {
        $this->provideFullTestSet();

        $this->commandBus->dispatch(new BuildPhotosHtml());
        $this->assertFileSystemWrites($this->getContainer()->get('build.storage'));
    }
}
