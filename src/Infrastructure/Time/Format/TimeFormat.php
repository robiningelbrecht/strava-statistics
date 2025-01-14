<?php

declare(strict_types=1);

namespace App\Infrastructure\Time\Format;

enum TimeFormat: int
{
    case TWENTY_FOUR = 24;
    case AM_PM = 12;
}
