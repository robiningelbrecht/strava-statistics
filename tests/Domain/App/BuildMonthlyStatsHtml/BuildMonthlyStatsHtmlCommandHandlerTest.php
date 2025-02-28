<?php

namespace App\Tests\Domain\App\BuildMonthlyStatsHtml;

use App\Domain\App\BuildMonthlyStatsHtml\BuildMonthlyStatsHtml;
use App\Infrastructure\CQRS\DomainCommand;
use App\Infrastructure\ValueObject\Time\SerializableDateTime;
use App\Tests\Domain\App\BuildAppFilesTestCase;

class BuildMonthlyStatsHtmlCommandHandlerTest extends BuildAppFilesTestCase
{
    protected function getDomainCommand(): DomainCommand
    {
        return new BuildMonthlyStatsHtml(SerializableDateTime::fromString('2023-10-17 16:15:04'));
    }
}
