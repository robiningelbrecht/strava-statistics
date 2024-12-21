<?php

namespace App\Infrastructure\Twig;

use App\Domain\Measurement\UnitSystem;
use Twig\Environment;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;
use Twig\TwigFunction;

final class TwigExtensions extends AbstractExtension
{
    public function __construct(
        private readonly Environment $twig,
        private readonly UnitSystem $unitSystem,
    ) {
    }

    #[\Override]
    public function getFilters(): array
    {
        return [
            new TwigFilter('repeat', StrRepeatTwigExtension::doRepeat(...)),
            new TwigFilter('ellipses', StrEllipsisTwigExtension::doEllipses(...)),
            new TwigFilter('formatNumber', FormatNumberTwigExtension::doFormat(...)),
            new TwigFilter('convertMeasurement', [new ConvertMeasurementTwigExtension($this->unitSystem), 'doConversion']),
        ];
    }

    #[\Override]
    public function getFunctions(): array
    {
        return [
            new TwigFunction('render', [new RenderTemplateTwigExtension($this->twig), 'render']),
            new TwigFunction('renderComponent', [new RenderTemplateTwigExtension($this->twig), 'renderComponent']),
            new TwigFunction('renderSvg', [new RenderTemplateTwigExtension($this->twig), 'renderSvg']),
        ];
    }
}
