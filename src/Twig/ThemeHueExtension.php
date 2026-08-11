<?php

namespace Figuralchor\ContaoBundle\Twig;

use Twig\Extension\AbstractExtension;
use Twig\Extension\GlobalsInterface;
use Figuralchor\ContaoBundle\Service\ThemeHueGenerator;

class ThemeHueExtension extends AbstractExtension implements GlobalsInterface
{
    public function __construct(
        private ThemeHueGenerator $generator
    ) {}

    public function getGlobals(): array
    {
        return [
            'themeHue' => $this->generator->getHue(),
        ];
    }
}
