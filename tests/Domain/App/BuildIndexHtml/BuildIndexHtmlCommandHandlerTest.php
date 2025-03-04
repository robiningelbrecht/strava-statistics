<?php

namespace App\Tests\Domain\App\BuildIndexHtml;

use App\Domain\App\BuildIndexHtml\BuildIndexHtml;
use App\Infrastructure\ValueObject\Time\SerializableDateTime;
use App\Tests\Domain\App\BuildAppFilesTestCase;

class BuildIndexHtmlCommandHandlerTest extends BuildAppFilesTestCase
{
    public function testHandle(): void
    {
        $this->provideFullTestSet();

        $this->commandBus->dispatch(new BuildIndexHtml(SerializableDateTime::fromString('2023-10-17 16:15:04')));
        $this->assertFileSystemWrites($this->getContainer()->get('build.storage'));
    }
}
