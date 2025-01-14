<?php

declare(strict_types=1);

namespace App\Infrastructure\Time\Format;

enum DateFormat: string
{
    case DAY_MONTH_YEAR = 'DAY-MONTH-YEAR';
    case MONTH_DAY_YEAR = 'MONTH-DAY-YEAR';
}
