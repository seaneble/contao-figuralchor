<?php

namespace Figuralchor\ContaoBundle\Module;

use Contao\BackendTemplate;
use Contao\PageModel;
use Contao\StringUtil;
use Contao\System;
use Figuralchor\ContaoBundle\Model\ConcertModel;

/**
 * Front end module "concert list" - used both as the compact homepage tile
 * (no jumpTo, so items render as plain text - the "minus the sorting title
 * link" variant) and as the more verbose list on the "Konzerte" page (jumpTo
 * set to a concertreader page), differing only by the selected item template.
 */
class ModuleConcertList extends ModuleConcert
{
	protected $strTemplate = 'mod_concertlist';

	public function generate()
	{
		$request = System::getContainer()->get('request_stack')->getCurrentRequest();

		if ($request && System::getContainer()->get('contao.routing.scope_matcher')->isBackendRequest($request))
		{
			$objTemplate = new BackendTemplate('be_wildcard');
			$objTemplate->wildcard = '### ' . $GLOBALS['TL_LANG']['FMD']['concertlist'][0] . ' ###';
			$objTemplate->title = $this->headline;
			$objTemplate->id = $this->id;
			$objTemplate->link = $this->name;
			$objTemplate->href = StringUtil::specialcharsUrl(System::getContainer()->get('router')->generate('contao_backend', array('do' => 'themes', 'table' => 'tl_module', 'act' => 'edit', 'id' => $this->id)));

			return $objTemplate->parse();
		}

		return parent::generate();
	}

	protected function compile()
	{
		$this->Template->articles = array();
		$this->Template->empty = $GLOBALS['TL_LANG']['MSC']['emptyList'];

		$limit = $this->numberOfItems > 0 ? $this->numberOfItems : null;
		$objConcerts = ConcertModel::findPublished($limit);

		if ($objConcerts === null)
		{
			return;
		}

		// Only set when this module instance has a reader page configured -
		// the homepage instance leaves jumpTo empty, so its items render as
		// plain text (no per-item link), by design.
		$objJumpTo = $this->jumpTo ? PageModel::findById($this->jumpTo) : null;

		$arrArticles = array();

		foreach ($objConcerts as $objConcert)
		{
			$arrArticles[] = $this->parseArticle($objConcert, $objJumpTo);
		}

		$this->Template->articles = $arrArticles;
	}
}
