<?php

namespace App\Tests\Domain\App\BuildEddingtonHtml;

use App\Domain\App\BuildEddingtonHtml\BuildEddingtonHtml;
use App\Infrastructure\CQRS\DomainCommand;
use App\Infrastructure\ValueObject\Time\SerializableDateTime;
use App\Tests\Domain\App\BuildAppFilesTestCase;

class BuildEddingtonHtmlCommandHandlerTest extends BuildAppFilesTestCase
{
    protected function getDomainCommand(): DomainCommand
    {
        return new BuildEddingtonHtml(SerializableDateTime::fromString('2023-10-17 16:15:04'));
    }
}
