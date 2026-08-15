<?php

namespace Figuralchor\ContaoBundle\Module;

use Contao\BackendTemplate;
use Contao\CoreBundle\Exception\PageNotFoundException;
use Contao\Environment;
use Contao\Input;
use Contao\PageModel;
use Contao\StringUtil;
use Contao\System;
use Figuralchor\ContaoBundle\Model\ConcertModel;

/**
 * Front end module "concert reader" - renders one full concert, read from
 * the "auto_item" URL fragment, mirroring ModuleNewsReader.
 */
class ModuleConcertReader extends ModuleConcert
{
	protected $strTemplate = 'mod_concertreader';

	public function generate()
	{
		$request = System::getContainer()->get('request_stack')->getCurrentRequest();

		if ($request && System::getContainer()->get('contao.routing.scope_matcher')->isBackendRequest($request))
		{
			$objTemplate = new BackendTemplate('be_wildcard');
			$objTemplate->wildcard = '### ' . $GLOBALS['TL_LANG']['FMD']['concertreader'][0] . ' ###';
			$objTemplate->title = $this->headline;
			$objTemplate->id = $this->id;
			$objTemplate->link = $this->name;
			$objTemplate->href = StringUtil::specialcharsUrl(System::getContainer()->get('router')->generate('contao_backend', array('do' => 'themes', 'table' => 'tl_module', 'act' => 'edit', 'id' => $this->id)));

			return $objTemplate->parse();
		}

		// Return an empty string if "auto_item" is not set, so list and reader can share one page
		if (Input::get('auto_item') === null)
		{
			return '';
		}

		return parent::generate();
	}

	protected function compile()
	{
		$this->Template->articles = '';

		if ($this->overviewPage && ($objOverviewPage = PageModel::findById($this->overviewPage)))
		{
			$this->Template->referer = $objOverviewPage->getFrontendUrl();
			$this->Template->back = $this->customLabel ?: $GLOBALS['TL_LANG']['MSC']['concertOverview'];
		}

		$objConcert = ConcertModel::findPublishedByIdOrAlias(Input::get('auto_item'));

		if ($objConcert === null)
		{
			throw new PageNotFoundException('Page not found: ' . Environment::get('uri'));
		}

		$this->Template->articles = $this->parseArticle($objConcert, null, true);
	}
}
