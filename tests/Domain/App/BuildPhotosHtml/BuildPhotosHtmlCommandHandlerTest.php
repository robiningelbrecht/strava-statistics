<?php

namespace App\Tests\Domain\App\BuildPhotosHtml;

use App\Domain\App\BuildPhotosHtml\BuildPhotosHtml;
use App\Infrastructure\CQRS\DomainCommand;
use App\Tests\Domain\App\BuildAppFilesTestCase;

class BuildPhotosHtmlCommandHandlerTest extends BuildAppFilesTestCase
{
    protected function getDomainCommand(): DomainCommand
    {
        return new BuildPhotosHtml();
    }
}
