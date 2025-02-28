<?php

namespace App\Tests\Domain\App\BuildSegmentsHtml;

use App\Domain\App\BuildSegmentsHtml\BuildSegmentsHtml;
use App\Infrastructure\CQRS\DomainCommand;
use App\Infrastructure\ValueObject\Time\SerializableDateTime;
use App\Tests\Domain\App\BuildAppFilesTestCase;

class BuildSegmentsHtmlCommandHandlerTest extends BuildAppFilesTestCase
{
    protected function getDomainCommand(): DomainCommand
    {
        return new BuildSegmentsHtml(SerializableDateTime::fromString('2023-10-17 16:15:04'));
    }
}
