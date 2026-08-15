<?php

use Figuralchor\ContaoBundle\Model\ConcertModel;
use Figuralchor\ContaoBundle\Module\ModuleConcertList;
use Figuralchor\ContaoBundle\Module\ModuleConcertReader;

// Back end modules
$GLOBALS['BE_MOD']['content']['concert'] = array
(
	'tables' => array('tl_concert'),
);

// Front end modules
$GLOBALS['FE_MOD']['concert'] = array
(
	'concertlist'   => ModuleConcertList::class,
	'concertreader' => ModuleConcertReader::class,
);

// Models
$GLOBALS['TL_MODELS']['tl_concert'] = ConcertModel::class;
