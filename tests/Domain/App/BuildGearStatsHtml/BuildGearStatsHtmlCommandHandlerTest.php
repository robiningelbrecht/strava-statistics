<?php

namespace App\Tests\Domain\App\BuildGearStatsHtml;

use App\Domain\App\BuildGearStatsHtml\BuildGearStatsHtml;
use App\Infrastructure\CQRS\DomainCommand;
use App\Infrastructure\ValueObject\Time\SerializableDateTime;
use App\Tests\Domain\App\BuildAppFilesTestCase;

class BuildGearStatsHtmlCommandHandlerTest extends BuildAppFilesTestCase
{
    protected function getDomainCommand(): DomainCommand
    {
        return new BuildGearStatsHtml(SerializableDateTime::fromString('2023-10-17 16:15:04'));
    }
}
