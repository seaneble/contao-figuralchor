<?php

use Contao\Controller;

// Palettes for the two new "concert" front end module types. Reuses core's
// existing generic tl_module fields (jumpTo, overviewPage, numberOfItems,
// imgSize, customLabel, cssID, protected) verbatim - only concert_template
// is new, mirroring tl_module.news_template from contao/news-bundle.
$GLOBALS['TL_DCA']['tl_module']['palettes']['concertlist']   = '{title_legend},name,headline,type;{config_legend},numberOfItems;{redirect_legend:hide},jumpTo;{template_legend:hide},concert_template,customTpl,imgSize;{protected_legend:hide},protected;{expert_legend:hide},cssID';
$GLOBALS['TL_DCA']['tl_module']['palettes']['concertreader'] = '{title_legend},name,headline,type;{reader_legend:hide},overviewPage,customLabel;{template_legend:hide},concert_template,customTpl,imgSize;{protected_legend:hide},protected;{expert_legend:hide},cssID';

$GLOBALS['TL_DCA']['tl_module']['fields']['concert_template'] = array
(
	'inputType'        => 'select',
	'options_callback' => static function () {
		return Controller::getTemplateGroup('concert_');
	},
	'eval'             => array('chosen' => true, 'includeBlankOption' => true, 'tl_class' => 'w50'),
	'sql'              => "varchar(64) COLLATE ascii_bin NOT NULL default ''",
);
