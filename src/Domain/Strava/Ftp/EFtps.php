<?php

declare(strict_types=1);

namespace App\Domain\Strava\Ftp;

use App\Infrastructure\ValueObject\Collection;

/**
 * @extends Collection<EFtp>
 */
class EFtps extends Collection
{
    public function getItemClassName(): string
    {
        return EFtp::class;
    }
}
