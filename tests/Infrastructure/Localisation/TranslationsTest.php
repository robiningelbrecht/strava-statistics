<?php

namespace App\Tests\Infrastructure\Localisation;

use App\Infrastructure\Localisation\Locale;
use App\Infrastructure\ValueObject\String\KernelProjectDir;
use App\Tests\ContainerTestCase;
use Spatie\Snapshots\MatchesSnapshots;
use Symfony\Component\Translation\Extractor\ExtractorInterface;
use Symfony\Component\Translation\MessageCatalogue;
use Symfony\Component\Yaml\Yaml;

class TranslationsTest extends ContainerTestCase
{
    use MatchesSnapshots;

    private KernelProjectDir $kernelProjectDir;
    private ExtractorInterface $extractor;

    public function testAllTranslationsHaveBeenExtracted(): void
    {
        $messages = new MessageCatalogue(Locale::en_US->value);
        $this->extractor->extract($this->kernelProjectDir.'/templates', $messages);
        $this->extractor->extract($this->kernelProjectDir.'/src', $messages);
        $translatables = $messages->all()['messages'];

        $translatableKeys = array_keys($translatables ?? []);

        foreach (Locale::cases() as $locale) {
            $translationFilePath = sprintf('%s/translations/messages%s.%s.yaml', $this->kernelProjectDir, MessageCatalogue::INTL_DOMAIN_SUFFIX, $locale->value);
            if (!file_exists($translationFilePath)) {
                $this->fail(sprintf('Not all translations for locale %s have been exported. Please run "make translation-extract"', $locale->value));
            }

            $parsedTranslations = Yaml::parse(file_get_contents($translationFilePath));
            $this->assertEqualsCanonicalizing(
                $translatableKeys,
                array_keys($parsedTranslations),
                sprintf('Not all translations for locale %s have been exported. Please run "make translation-extract"', $locale->value)
            );
        }

        ksort($translatables);
        $this->assertMatchesJsonSnapshot($translatables);
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->kernelProjectDir = $this->getContainer()->get(KernelProjectDir::class);
        $this->extractor = $this->getContainer()->get(ExtractorInterface::class);
    }
}