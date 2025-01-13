<?php

declare(strict_types=1);

namespace App\Infrastructure\Repository;

use Doctrine\DBAL\Connection;

abstract readonly class DbalRepository
{
    public function __construct(
        protected Connection $connection,
    ) {
    }
}
