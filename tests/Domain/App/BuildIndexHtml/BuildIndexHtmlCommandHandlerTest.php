<?php

namespace App\Tests\Domain\App\BuildIndexHtml;

use App\Domain\App\BuildIndexHtml\BuildIndexHtml;
use App\Infrastructure\CQRS\DomainCommand;
use App\Infrastructure\ValueObject\Time\SerializableDateTime;
use App\Tests\Domain\App\BuildAppFilesTestCase;

class BuildIndexHtmlCommandHandlerTest extends BuildAppFilesTestCase
{
    protected function getDomainCommand(): DomainCommand
    {
        return new BuildIndexHtml(SerializableDateTime::fromString('2023-10-17 16:15:04'));
    }
}
