<?php

namespace App\Tests\Domain\Strava;

use App\Domain\Strava\Activity\SportType\SportType;
use App\Tests\ContainerTestCase;
use Spatie\Snapshots\MatchesSnapshots;
use Twig\Environment;

class SportTypeTest extends ContainerTestCase
{
    use MatchesSnapshots;

    public function testGetSvgIcon(): void
    {
        /** @var Environment $twig */
        $twig = $this->getContainer()->get(Environment::class);

        $snapshot = [];
        foreach (SportType::cases() as $sportType) {
            $snapshot[] = $twig->load('html/svg/sport-type/svg-'.$sportType->getSvgIcon().'.html.twig')->render();
        }
        $this->assertMatchesHtmlSnapshot(implode(PHP_EOL, $snapshot));
    }

    public function testGetSingularLabel(): void
    {
        $snapshot = [];
        foreach (SportType::cases() as $sportType) {
            $snapshot[] = $sportType->getSingularLabel();
        }
        $this->assertMatchesJsonSnapshot($snapshot);
    }

    public function testGetPluralLabel(): void
    {
        $snapshot = [];
        foreach (SportType::cases() as $sportType) {
            $snapshot[] = $sportType->getPluralLabel();
        }
        $this->assertMatchesJsonSnapshot($snapshot);
    }
}
