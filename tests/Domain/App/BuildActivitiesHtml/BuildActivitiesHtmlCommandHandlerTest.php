<?php

namespace App\Tests\Domain\App\BuildActivitiesHtml;

use App\Domain\App\BuildActivitiesHtml\BuildActivitiesHtml;
use App\Infrastructure\CQRS\DomainCommand;
use App\Infrastructure\ValueObject\Time\SerializableDateTime;
use App\Tests\Domain\App\BuildAppFilesTestCase;

class BuildActivitiesHtmlCommandHandlerTest extends BuildAppFilesTestCase
{
    protected function getDomainCommand(): DomainCommand
    {
        return new BuildActivitiesHtml(SerializableDateTime::fromString('2023-10-17 16:15:04'));
    }
}
