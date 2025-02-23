<?php

namespace App\Tests\Domain\App\ConfigureAppLocale;

use App\Domain\App\ConfigureAppLocale\ConfigureAppLocale;
use App\Domain\App\ConfigureAppLocale\ConfigureAppLocaleCommandHandler;
use App\Infrastructure\Localisation\Locale;
use App\Tests\ContainerTestCase;
use Carbon\Carbon;
use Symfony\Component\Translation\LocaleSwitcher;

class ConfigureAppLocaleCommandHandlerTest extends ContainerTestCase
{
    private ConfigureAppLocaleCommandHandler $configureAppLocaleCommandHandler;
    private LocaleSwitcher $localeSwitcher;

    public function testHandle(): void
    {
        $this->assertEquals(
            Locale::en_US->value,
            $this->localeSwitcher->getLocale()
        );
        $this->assertEquals(
            'en_US',
            Carbon::getLocale()
        );

        $this->configureAppLocaleCommandHandler->handle(new ConfigureAppLocale());

        $this->assertEquals(
            Locale::fr_FR->value,
            $this->localeSwitcher->getLocale()
        );
        $this->assertEquals(
            Locale::fr_FR->value,
            Carbon::getLocale()
        );
    }

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->configureAppLocaleCommandHandler = new ConfigureAppLocaleCommandHandler(
            $this->localeSwitcher = $this->getContainer()->get(LocaleSwitcher::class),
            Locale::fr_FR
        );
    }
}
