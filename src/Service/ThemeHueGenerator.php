<?php

namespace Figuralchor\ContaoBundle\Service;

use Symfony\Component\HttpFoundation\RequestStack;

class ThemeHueGenerator
{
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

        $sessionId = $request->getSession()->getId();

        return abs(crc32($sessionId)) % 360;
    }
}
