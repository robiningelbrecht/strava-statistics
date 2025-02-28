<?php

namespace App\Tests\Domain\App\BuildChallengesHtml;

use App\Domain\App\BuildChallengesHtml\BuildChallengesHtml;
use App\Infrastructure\CQRS\DomainCommand;
use App\Infrastructure\ValueObject\Time\SerializableDateTime;
use App\Tests\Domain\App\BuildAppFilesTestCase;

class BuildChallengesHtmlCommandHandlerTest extends BuildAppFilesTestCase
{
    protected function getDomainCommand(): DomainCommand
    {
        return new BuildChallengesHtml(SerializableDateTime::fromString('2023-10-17 16:15:04'));
    }
}
