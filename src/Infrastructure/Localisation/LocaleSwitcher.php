<?php

declare(strict_types=1);

namespace App\Infrastructure\Localisation;

use Carbon\Carbon;
use Symfony\Component\Translation\LocaleSwitcher as SymfonyLocaleSwitcher;

final readonly class LocaleSwitcher
{
    public function __construct(
        private SymfonyLocaleSwitcher $localeSwitcher,
        private Locale $locale,
    ) {
    }

    public function setLocale(): void
    {
        $this->localeSwitcher->setLocale($this->locale->value);
        Carbon::setLocale($this->locale->value);
    }
}
