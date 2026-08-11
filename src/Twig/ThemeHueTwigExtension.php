<?php

namespace Figuralchor\ContaoBundle\Twig;

use Figuralchor\ContaoBundle\Service\ThemeHueGenerator;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class ThemeHueTwigExtension extends AbstractExtension
{
    public function __construct(
        private readonly ThemeHueGenerator $generator,
    ) {
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('figuralchor_theme_hue', [$this->generator, 'getHue']),
        ];
    }
}
