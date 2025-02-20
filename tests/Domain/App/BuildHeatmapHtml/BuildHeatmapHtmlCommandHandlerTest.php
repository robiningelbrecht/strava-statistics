<?php

namespace App\Tests\Domain\App\BuildHeatmapHtml;

use App\Domain\App\BuildHeatmapHtml\BuildHeatmapHtml;
use App\Infrastructure\CQRS\Bus\DomainCommand;
use App\Infrastructure\ValueObject\Time\SerializableDateTime;
use App\Tests\Domain\App\BuildAppFilesTestCase;

class BuildHeatmapHtmlCommandHandlerTest extends BuildAppFilesTestCase
{
    protected function getDomainCommand(): DomainCommand
    {
        return new BuildHeatmapHtml(SerializableDateTime::fromString('2023-10-17 16:15:04'));
    }
}
