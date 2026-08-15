<?php

use Contao\Backend;
use Contao\Config;
use Contao\Database;
use Contao\DataContainer;
use Contao\DC_Table;
use Contao\Date;
use Contao\System;

$GLOBALS['TL_DCA']['tl_concert'] = array
(
	// Config
	'config' => array
	(
		'dataContainer'    => DC_Table::class,
		'enableVersioning' => true,
		'markAsCopy'       => 'title',
		'sql'              => array
		(
			'keys' => array
			(
				'id'    => 'primary',
				'alias' => 'index',
			),
		),
	),

	// List
	'list' => array
	(
		'sorting' => array
		(
			'mode'               => DataContainer::MODE_SORTED,
			'fields'             => array('year DESC', 'title ASC'),
			'panelLayout'        => 'search,filter,limit',
			'defaultSearchField' => 'title',
		),
		'label' => array
		(
			'fields' => array('year', 'title'),
			'format' => '%s – %s',
		),
		'operations' => array
		(
			'edit',
			'copy',
			'delete',
			'toggle' => array
			(
				'href'         => 'act=toggle&amp;field=published',
				'icon'         => 'visible.svg',
				'primary'      => true,
				'showInHeader' => true,
			),
			'show',
		),
	),

	// Palettes
	'palettes' => array
	(
		'default' => '{title_legend},year,title,alias;{teaser_legend},teaser,description;{image_legend},posterSRC;{events_legend},events;{publish_legend},published',
	),

	// Fields
	'fields' => array
	(
		'id' => array
		(
			'sql' => "int(10) unsigned NOT NULL auto_increment",
		),
		'tstamp' => array
		(
			'sql' => "int(10) unsigned NOT NULL default 0",
		),
		'year' => array
		(
			'search'    => true,
			'filter'    => true,
			'sorting'   => true,
			'inputType' => 'text',
			'eval'      => array('mandatory' => true, 'rgxp' => 'digit', 'maxlength' => 4, 'tl_class' => 'w50'),
			'sql'       => "smallint(4) unsigned NOT NULL default 0",
		),
		'title' => array
		(
			'search'    => true,
			'sorting'   => true,
			'inputType' => 'text',
			'eval'      => array('mandatory' => true, 'maxlength' => 255, 'tl_class' => 'w50'),
			'sql'       => "varchar(255) NOT NULL default ''",
		),
		'alias' => array
		(
			'search'        => true,
			'inputType'     => 'text',
			'eval'          => array('rgxp' => 'alias', 'doNotCopy' => true, 'unique' => true, 'maxlength' => 255, 'tl_class' => 'clr'),
			'save_callback' => array
			(
				array('tl_concert', 'generateAlias'),
			),
			'sql'           => "varchar(255) BINARY NOT NULL default ''",
		),
		'teaser' => array
		(
			'search'    => true,
			'inputType' => 'textarea',
			'eval'      => array('rows' => 3, 'maxlength' => 500, 'tl_class' => 'clr'),
			'sql'       => "text NULL",
		),
		'description' => array
		(
			'search'    => true,
			'inputType' => 'textarea',
			'eval'      => array('rte' => 'tinyMCE', 'tl_class' => 'clr'),
			'sql'       => "text NULL",
		),
		'posterSRC' => array
		(
			'inputType' => 'fileTree',
			'eval'      => array('fieldType' => 'radio', 'filesOnly' => true, 'extensions' => '%contao.image.valid_extensions%', 'tl_class' => 'clr'),
			'sql'       => "binary(16) NULL",
		),
		'events' => array
		(
			'inputType'        => 'select',
			'options_callback' => array('tl_concert', 'getCalendarEvents'),
			'foreignKey'       => 'tl_calendar_events.title',
			'eval'             => array('multiple' => true, 'chosen' => true, 'tl_class' => 'clr'),
			'sql'              => "blob NULL",
			'relation'         => array('type' => 'hasMany', 'load' => 'lazy'),
		),
		'published' => array
		(
			'toggle'    => true,
			'filter'    => true,
			'inputType' => 'checkbox',
			'eval'      => array('doNotCopy' => true),
			'sql'       => array('type' => 'boolean', 'default' => false),
		),
	),
);

class tl_concert extends Backend
{
	/**
	 * Auto-generate an alias from year + title if none was given, mirroring
	 * tl_news::generateAlias (contao/news-bundle/contao/dca/tl_news.php).
	 */
	public function generateAlias($varValue, DataContainer $dc)
	{
		$aliasExists = static function (string $alias) use ($dc): bool {
			$result = Database::getInstance()
				->prepare("SELECT id FROM tl_concert WHERE alias=? AND id!=?")
				->execute($alias, $dc->id);

			return $result->numRows > 0;
		};

		if (!$varValue)
		{
			$varValue = System::getContainer()->get('contao.slug')->generate($dc->activeRecord->year . '-' . $dc->activeRecord->title, [], $aliasExists);
		}
		elseif ($aliasExists($varValue))
		{
			throw new Exception(sprintf($GLOBALS['TL_LANG']['ERR']['aliasExists'], $varValue));
		}

		return $varValue;
	}

	/**
	 * Options for the "events" multi-select, grouped by calendar name so the
	 * (searchable, via eval.chosen) dropdown stays usable once there are many.
	 * Restricted to the calendar configured under System Settings >
	 * "Konzerte" (concert_calendar), if one has been set.
	 */
	public function getCalendarEvents(): array
	{
		$groups = array();
		$intCalendar = (int) Config::get('concert_calendar');

		$sql = "SELECT e.id, e.title, e.startTime, c.title AS calendarTitle FROM tl_calendar_events e LEFT JOIN tl_calendar c ON c.id = e.pid";
		$values = array();

		if ($intCalendar > 0)
		{
			$sql .= " WHERE e.pid=?";
			$values[] = $intCalendar;
		}

		$sql .= " ORDER BY c.title, e.startTime DESC";

		$result = Database::getInstance()->prepare($sql)->execute(...$values);

		while ($result->next())
		{
			$label = $result->title . ' (' . Date::parse(Config::get('dateFormat'), $result->startTime) . ')';
			$groups[$result->calendarTitle ?: '-'][$result->id] = $label;
		}

		return $groups;
	}
}
