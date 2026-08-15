<?php

namespace Figuralchor\ContaoBundle\Model;

use Contao\Model;
use Contao\Model\Collection;

/**
 * Reads and writes concerts (tl_concert).
 *
 * @property integer     $id
 * @property integer     $tstamp
 * @property integer     $year
 * @property string      $title
 * @property string      $alias
 * @property string|null $teaser
 * @property string|null $description
 * @property string|null $posterSRC
 * @property string|null $events
 * @property boolean     $published
 *
 * @method static ConcertModel|null findById($id, array $opt=array())
 * @method static ConcertModel|null findByPk($id, array $opt=array())
 * @method static ConcertModel|null findByIdOrAlias($val, array $opt=array())
 * @method static Collection|ConcertModel[]|ConcertModel|null findByPk($varValue, array $arrOptions=array())
 */
class ConcertModel extends Model
{
	protected static $strTable = 'tl_concert';

	/**
	 * Find a published concert by its numeric ID or its alias.
	 *
	 * @param mixed $varId
	 *
	 * @return ConcertModel|null
	 */
	public static function findPublishedByIdOrAlias($varId, array $arrOptions = array())
	{
		$t = static::$strTable;
		$arrColumns = !preg_match('/^[1-9]\d*$/', $varId) ? array("$t.alias=?") : array("$t.id=?");

		if (!static::isPreviewMode($arrOptions))
		{
			$arrColumns[] = "$t.published=1";
		}

		return static::findOneBy($arrColumns, array($varId), $arrOptions);
	}

	/**
	 * Find published concerts, newest year first by default.
	 *
	 * @param integer|null $intLimit
	 * @param integer      $intOffset
	 *
	 * @return Collection|ConcertModel[]|ConcertModel|null
	 */
	public static function findPublished($intLimit = null, $intOffset = 0, array $arrOptions = array())
	{
		$t = static::$strTable;
		$arrColumns = array();

		if (!static::isPreviewMode($arrOptions))
		{
			$arrColumns[] = "$t.published=1";
		}

		if (!isset($arrOptions['order']))
		{
			$arrOptions['order'] = "$t.year DESC, $t.title ASC";
		}

		if ($intLimit)
		{
			$arrOptions['limit'] = $intLimit;
		}

		if ($intOffset)
		{
			$arrOptions['offset'] = $intOffset;
		}

		if (empty($arrColumns))
		{
			return static::findAll($arrOptions);
		}

		return static::findBy($arrColumns, null, $arrOptions);
	}
}
