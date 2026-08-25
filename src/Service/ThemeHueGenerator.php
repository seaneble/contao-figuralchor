<?php

namespace Figuralchor\ContaoBundle\Service;

use Symfony\Component\HttpFoundation\RequestStack;

class ThemeHueGenerator
{
    private const SESSION_KEY = 'figuralchor_theme_hue';

    /**
     * Input-hue ranges [start, end] (degrees, end exclusive-via-mod-360)
     * that render as a visually distinct color under theme.css's accent
     * formula, oklch(48% 0.11 H). A plain uniform 0-359 draw weights each
     * band by its raw width, so e.g. the cyan/turquoise band alone
     * (156-293, 138 of 360 degrees) got picked for 38% of sessions while
     * red (23-38) got 4%. Picking a band first, then a hue uniformly
     * within it, gives every band an equal ~1/9 chance instead. Recompute
     * these ranges if --color-accent's lightness/chroma in theme.css ever
     * changes - they're specific to that exact formula.
     */
    private const HUE_BANDS = [
        [351, 366], // magenta (wraps past 360)
        [7, 22],    // pink-red
        [23, 38],   // red
        [39, 88],   // orange
        [89, 119],  // yellow / olive-brown
        [120, 155], // green
        [156, 293], // cyan / turquoise
        [294, 320], // blue
        [321, 350], // purple
    ];

    public function __construct(
        private readonly RequestStack $requestStack,
    ) {
    }

    public function getHue(): int
    {
        $request = $this->requestStack->getCurrentRequest();

        if (!$request || !$request->hasSession()) {
            return 180;
        }

        $session = $request->getSession();

        if (!$session->has(self::SESSION_KEY)) {
            $session->set(self::SESSION_KEY, $this->rollHue());
        }

        return $session->get(self::SESSION_KEY);
    }

    private function rollHue(): int
    {
        [$lo, $hi] = self::HUE_BANDS[random_int(0, \count(self::HUE_BANDS) - 1)];

        return random_int($lo, $hi) % 360;
    }
}
