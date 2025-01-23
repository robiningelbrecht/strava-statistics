<?php

declare(strict_types=1);

namespace App\Infrastructure\Localisation;

enum Locale: string
{
    case en_US = 'en_US';
    case nl_BE = 'nl_BE';
    case fr_FR = 'fr_FR';
}
