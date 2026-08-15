<?php

use Contao\Backend;
use Contao\Database;

// Adds a single global "which calendar do concert events come from" setting
// to System Settings, so it isn't buried in a per-record field. tl_settings
// uses DC_File (a config file, not a DB table), so no sql key/migration is
// needed for this field.
$GLOBALS['TL_DCA']['tl_settings']['palettes']['default'] .= ';{concert_legend},concert_calendar';

$GLOBALS['TL_DCA']['tl_settings']['fields']['concert_calendar'] = array
(
	'inputType'        => 'select',
	'options_callback' => array('tl_settings_concert', 'getCalendars'),
	'eval'             => array('includeBlankOption' => true, 'chosen' => true, 'tl_class' => 'w50'),
);

class tl_settings_concert extends Backend
{
	/**
	 * List all calendars, for the "which calendar are concert events picked
	 * from" global setting.
	 */
	public function getCalendars(): array
	{
		$options = array();

		$result = Database::getInstance()->execute("SELECT id, title FROM tl_calendar ORDER BY title");

		while ($result->next())
		{
			$options[$result->id] = $result->title;
		}

		return $options;
	}
}
