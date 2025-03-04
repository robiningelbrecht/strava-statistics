<?php

namespace App\Tests\Domain\App\BuildEddingtonHtml;

use App\Domain\App\BuildEddingtonHtml\BuildEddingtonHtml;
use App\Infrastructure\ValueObject\Time\SerializableDateTime;
use App\Tests\Domain\App\BuildAppFilesTestCase;

class BuildEddingtonHtmlCommandHandlerTest extends BuildAppFilesTestCase
{
    public function testHandle(): void
    {
        $this->provideFullTestSet();

        $this->commandBus->dispatch(new BuildEddingtonHtml(SerializableDateTime::fromString('2023-10-17 16:15:04')));
        $this->assertFileSystemWrites($this->getContainer()->get('build.storage'));
    }
}
