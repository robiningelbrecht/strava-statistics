<?php

namespace App\Tests\Domain\App\BuildGpxFiles;

use App\Domain\App\BuildGpxFiles\BuildGpxFiles;
use App\Infrastructure\CQRS\Bus\DomainCommand;
use App\Tests\Domain\App\BuildAppFilesTestCase;

class BuildGpxFilesCommandHandlerTest extends BuildAppFilesTestCase
{
    protected function getDomainCommand(): DomainCommand
    {
        return new BuildGpxFiles();
    }
}
