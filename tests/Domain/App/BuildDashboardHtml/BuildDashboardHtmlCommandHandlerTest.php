<?php

namespace App\Tests\Domain\App\BuildDashboardHtml;

use App\Domain\App\BuildDashboardHtml\BuildDashboardHtml;
use App\Infrastructure\CQRS\Bus\DomainCommand;
use App\Infrastructure\ValueObject\Time\SerializableDateTime;
use App\Tests\Domain\App\BuildAppFilesTestCase;

class BuildDashboardHtmlCommandHandlerTest extends BuildAppFilesTestCase
{
    protected function getDomainCommand(): DomainCommand
    {
        return new BuildDashboardHtml(SerializableDateTime::fromString('2023-10-17 16:15:04'));
    }
}
