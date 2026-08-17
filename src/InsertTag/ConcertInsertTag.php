<?php

namespace Figuralchor\ContaoBundle\InsertTag;

use Contao\CoreBundle\DependencyInjection\Attribute\AsInsertTag;
use Contao\CoreBundle\InsertTag\InsertTagResult;
use Contao\CoreBundle\InsertTag\OutputType;
use Contao\CoreBundle\InsertTag\Resolver\InsertTagResolverNestedResolvedInterface;
use Contao\CoreBundle\InsertTag\ResolvedInsertTag;
use Contao\ModuleModel;
use Contao\PageModel;
use Contao\StringUtil;
use Doctrine\DBAL\Connection;
use Figuralchor\ContaoBundle\Model\ConcertModel;

/**
 * {{concert::id}} - links to a concert's detail page, mirroring core's
 * {{article::id}}. Meant for event descriptions imported from the external
 * ICS feed, where an editor can't use Contao's page-link insert tag picker.
 */
#[AsInsertTag('concert')]
class ConcertInsertTag implements InsertTagResolverNestedResolvedInterface
{
    public function __construct(
        private readonly Connection $connection,
    ) {
    }

    public function __invoke(ResolvedInsertTag $insertTag): InsertTagResult
    {
        $objConcert = ConcertModel::findPublishedByIdOrAlias($insertTag->getParameters()->get(0));

        if ($objConcert === null) {
            return new InsertTagResult('', OutputType::text);
        }

        $objPage = $this->findReaderPage();

        if ($objPage === null) {
            return new InsertTagResult('', OutputType::text);
        }

        $strUrl = $objPage->getFrontendUrl('/' . ($objConcert->alias ?: $objConcert->id));

        return new InsertTagResult(
            \sprintf(
                '<a href="%s">%s</a>',
                StringUtil::specialcharsAttribute($strUrl),
                StringUtil::specialchars($objConcert->title),
            ),
            OutputType::html,
        );
    }

    /**
     * There's exactly one "concert reader" module on the site (see
     * README). Find the page it's placed on via the content element that
     * references it and that element's article - Contao has no direct
     * module-to-page relation to query instead.
     */
    private function findReaderPage(): PageModel|null
    {
        $objModule = ModuleModel::findOneBy('type', 'concertreader');

        if ($objModule === null) {
            return null;
        }

        $pageId = $this->connection->fetchOne(
            'SELECT a.pid FROM tl_content c JOIN tl_article a ON a.id = c.pid WHERE c.type = ? AND c.module = ? LIMIT 1',
            ['module', $objModule->id],
        );

        if (!$pageId) {
            return null;
        }

        return PageModel::findById($pageId);
    }
}
