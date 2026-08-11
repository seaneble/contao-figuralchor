<?php

namespace Figuralchor\ContaoBundle\InsertTag;

use Contao\CoreBundle\DependencyInjection\Attribute\AsInsertTag;
use Contao\CoreBundle\InsertTag\InsertTagResult;
use Contao\CoreBundle\InsertTag\OutputType;
use Contao\CoreBundle\InsertTag\Resolver\InsertTagResolverNestedResolvedInterface;
use Contao\CoreBundle\InsertTag\ResolvedInsertTag;
use Figuralchor\ContaoBundle\Service\ThemeHueGenerator;

#[AsInsertTag('theme_hue')]
class ThemeHueInsertTag implements InsertTagResolverNestedResolvedInterface
{
    public function __construct(
        private readonly ThemeHueGenerator $generator,
    ) {
    }

    public function __invoke(ResolvedInsertTag $insertTag): InsertTagResult
    {
        return new InsertTagResult((string) $this->generator->getHue(), OutputType::text);
    }
}
