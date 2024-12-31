<?php

namespace App\Tests\Domain\Strava\Ftp\ImportFtp;

use App\Domain\Strava\Ftp\ImportFtp\FtpValuesFromEnvFile;
use PHPUnit\Framework\TestCase;

class FtpValuesFromEnvFileTest extends TestCase
{
    public function testItShouldThrow(): void
    {
        $this->expectExceptionObject(new \InvalidArgumentException('Invalid FTP_VALUES detected in .env file. Make sure the string is valid JSON'));
        FtpValuesFromEnvFile::fromString('{"lol}');
    }
}
