<?php

namespace Figuralchor\ContaoBundle\Service;

use Symfony\Component\HttpFoundation\RequestStack;

class ThemeHueGenerator
{
    private const SESSION_KEY = 'figuralchor_theme_hue';

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
            $session->set(self::SESSION_KEY, random_int(0, 359));
        }

        return $session->get(self::SESSION_KEY);
    }
}
