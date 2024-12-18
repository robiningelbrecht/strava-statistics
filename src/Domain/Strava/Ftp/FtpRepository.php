<?php

namespace App\Domain\Strava\Ftp;

use App\Infrastructure\ValueObject\Time\SerializableDateTime;

interface FtpRepository
{
    public function removeAll(): void;

    public function save(Ftp $ftp): void;

    public function findAll(): Ftps;

    public function find(SerializableDateTime $dateTime): Ftp;
}
