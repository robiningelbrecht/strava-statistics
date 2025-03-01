<?php

namespace App\Tests\Domain\App\BuildGpxFiles;

use App\Domain\App\BuildGpxFiles\BuildGpxFiles;
use App\Tests\Domain\App\BuildAppFilesTestCase;

class BuildGpxFilesCommandHandlerTest extends BuildAppFilesTestCase
{
    public function testHandle(): void
    {
        $this->provideFullTestSet();

        $this->commandBus->dispatch(new BuildGpxFiles());
        $this->commandBus->dispatch(new BuildGpxFiles());

        $this->assertFileSystemWrites($this->getContainer()->get('file.storage'));
    }
}
