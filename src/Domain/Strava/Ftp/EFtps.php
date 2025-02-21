<?php

declare(strict_types=1);

namespace App\Domain\Strava\Ftp;

use App\Domain\Strava\Activity\ActivityType;
use App\Infrastructure\ValueObject\Collection;
use App\Infrastructure\ValueObject\Time\SerializableDateTime;

/**
 * @extends Collection<EFtp>
 */
class EFtps extends Collection
{
    public function getItemClassName(): string
    {
        return EFtp::class;
    }

    public function findForDate(SerializableDateTime $dateTime): ?EFtp
    {
        $startDate = (clone $dateTime)->modify('-8 weeks');
        $maxEftp = null;
        
        $filtered = $this->filter(function (EFtp $eftp) use ($startDate, $dateTime) {
            return $eftp->getSetOn() >= $startDate && $eftp->getSetOn() <= $dateTime;
        });
        
        if ($filtered->isEmpty()) {
            return null;
        }
        
        foreach ($filtered as $eftp) {
            if ($maxEftp === null || $eftp->getEftp() > $maxEftp->getEftp()) {
                $maxEftp = $eftp;
            }
        }
        
        return $maxEftp;
    }

    public function last()
    {
        if (empty($this->items)) {
            return null;
        }
        return $this->items[count($this->items) - 1];
    }
}
