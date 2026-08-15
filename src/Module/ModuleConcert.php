<?php

namespace Figuralchor\ContaoBundle\Module;

use Contao\CalendarEventsModel;
use Contao\Config;
use Contao\Date;
use Contao\FrontendTemplate;
use Contao\Module;
use Contao\PageModel;
use Contao\StringUtil;
use Contao\System;
use Figuralchor\ContaoBundle\Model\ConcertModel;
use Symfony\Component\Routing\Exception\ExceptionInterface;

/**
 * Shared rendering logic for the "concertlist" and "concertreader" front end
 * modules, mirroring contao/news-bundle's ModuleNews abstract base.
 */
abstract class ModuleConcert extends Module
{
	/**
	 * Render one concert through the selected item template and return the
	 * resulting HTML, mirroring ModuleNews::parseArticle().
	 */
	protected function parseArticle(ConcertModel $objConcert, ?PageModel $objJumpTo = null, bool $blnFull = false): string
	{
		$objTemplate = new FrontendTemplate($this->concert_template ?: ($blnFull ? 'concert_full' : 'concert_latest'));
		$objTemplate->setData($objConcert->row());

		$objTemplate->year = $objConcert->year;
		$objTemplate->title = $objConcert->title;
		$objTemplate->hasLink = false;

		if ($objJumpTo !== null)
		{
			$objTemplate->hasLink = true;
			$objTemplate->href = $objJumpTo->getFrontendUrl('/' . ($objConcert->alias ?: $objConcert->id));
			$objTemplate->more = $GLOBALS['TL_LANG']['MSC']['more'];
			$objTemplate->readMore = sprintf($GLOBALS['TL_LANG']['MSC']['readMore'], $objConcert->title);
		}

		$objTemplate->hasTeaser = (bool) $objConcert->teaser;
		$objTemplate->teaser = $objConcert->teaser;

		$objTemplate->hasDescription = (bool) $objConcert->description;
		$objTemplate->description = $objConcert->description;

		// Poster image
		$objTemplate->addImage = false;

		if ($objConcert->posterSRC)
		{
			$imgSize = $this->imgSize ?: null;

			$figureBuilder = System::getContainer()
				->get('contao.image.studio')
				->createFigureBuilder()
				->from($objConcert->posterSRC)
				->setSize($imgSize);

			if (null !== ($figure = $figureBuilder->buildIfResourceExists()))
			{
				$figure->applyLegacyTemplateData($objTemplate);
				$objTemplate->addImage = true;
			}
		}

		// Linked calendar events ("the exact happenings")
		$objTemplate->hasEvents = false;
		$objTemplate->events = array();

		$arrEventIds = StringUtil::deserialize($objConcert->events, true);

		if ($blnFull && !empty($arrEventIds))
		{
			$objEvents = CalendarEventsModel::findMultipleByIds($arrEventIds, array('order' => 'startTime ASC'));

			if ($objEvents !== null)
			{
				$urlGenerator = System::getContainer()->get('contao.routing.content_url_generator');
				$arrEvents = array();

				foreach ($objEvents as $objEvent)
				{
					$href = null;

					try
					{
						$href = $urlGenerator->generate($objEvent);
					}
					catch (ExceptionInterface)
					{
						// No route available (e.g. the calendar has no reader page) - render without a link
					}

					$arrEvents[] = array(
						'date'  => Date::parse(Config::get('dateFormat'), $objEvent->startTime),
						'title' => $objEvent->title,
						'href'  => $href,
					);
				}

				$objTemplate->hasEvents = true;
				$objTemplate->events = $arrEvents;
				$objTemplate->eventsHeadline = $GLOBALS['TL_LANG']['MSC']['concertDates'];
			}
		}

		return $objTemplate->parse();
	}
}
