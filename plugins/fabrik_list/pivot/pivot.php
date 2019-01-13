<?php
/**
 * Mutate the list data into a pivot table
 *
 * @package     Joomla.Plugin
 * @subpackage  Fabrik.list.pivot
 * @copyright   Copyright (C) 2005-2016  Media A-Team, Inc. - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

// No direct access
defined('_JEXEC') or die('Restricted access');

use Fabrik\Component\Fabrik\Administrator\Model\FabrikModel;
use Fabrik\Component\Fabrik\Site\Model\ListModel;
use Fabrik\Component\Fabrik\Site\Plugin\AbstractListPlugin;
use Joomla\CMS\Language\Text;
use Joomla\Database\DatabaseDriver;
use Joomla\Registry\Registry;
use Joomla\Utilities\ArrayHelper;
use Fabrik\Helpers\StringHelper as FStringHelper;
use Fabrik\Helpers\Worker;

/**
 * Mutate the list data into a pivot table
 *
 * @package     Joomla.Plugin
 * @subpackage  Fabrik.list.pivot
 * @since       3.1
 */
class PlgFabrik_ListPivot extends AbstractListPlugin
{
	/**
	 * Inject the select sum() fields into the list query JDatabaseQuery object
	 *
	 * @param   array $args Plugin call arguments
	 *
	 * @return  void
	 *
	 * @since 4.0
	 */
	public function onBuildQuerySelect($args)
	{
		if (!$query = $this->hasQuery($args))
		{
			return;
		}

		$sum = $this->sums();
		$query->select($sum);
	}

	/**
	 * Do the plugin arguments have a JDatabaseQuery among them
	 *
	 * @param   array $args Plugin call arguments
	 *
	 * @return  mixed  false if no JDatabaseQuery found otherwise returns JDatabaseQuery object
	 *
	 * @since 4.0
	 */
	private function hasQuery($args)
	{
		foreach ($args as $arg)
		{
			if (is_object($arg) && is_a($arg, DatabaseDriver::class))
			{
				return $arg;
			}
		}

		return false;
	}

	/**
	 * Inject the group by statement into the query object
	 *
	 * @param   array $args Plugin arguements
	 *
	 * @return  void
	 *
	 * @since 4.0
	 */
	public function onBuildQueryGroupBy($args)
	{
		if (!$query = $this->hasQuery($args))
		{
			return;
		}

		$query->clear('group');
		$query->group($this->group());
	}

	/**
	 * Build the group by sql statement
	 *
	 * @return string
	 *
	 * @since 4.0
	 */
	private function group()
	{
		$params = $this->getParams();
		$groups = explode(',', $params->get('pivot_group'));

		foreach ($groups as &$group)
		{
			$group = trim($group);
			//$group = FStringHelper::safeColName($group);
		}

		$group = implode(', ', $groups);

		return $group;
	}

	/**
	 * Build the sums() sql statement
	 *
	 * @return string
	 *
	 * @since 4.0
	 */
	private function sums()
	{
		$params = $this->getParams();
		$sums   = explode(',', $params->get('pivot_sum'));
		$db     = $this->model->getDb();
		$fn     = (int) $params->get('pivot_count', '0') == 1 ? 'COUNT' : 'SUM';

		foreach ($sums as &$sum)
		{
			$sum = trim($sum);
			$sum = FStringHelper::rtrimword($sum, '_raw');
			$as  = FStringHelper::safeColNameToArrayKey($sum);

			$statement = $fn . '(' . FStringHelper::safeColName($sum) . ')';
			$statement .= ' AS ' . $db->quoteName($as);
			$statement .= ', ' . $fn . '(' . FStringHelper::safeColName($sum) . ')';
			$statement .= ' AS ' . $db->quoteName($as . '_raw');

			$sum = $statement;
		}

		$sum = implode(', ', $sums);

		return $sum;
	}

	/**
	 *
	 * @return array
	 *
	 * @since 4.0
	 */
	private function getCols()
	{
		$params = $this->getParams();
		$xCol   = $params->get('pivot_xcol', '');
		$yCol   = $params->get('pivot_ycol', '');

		if ($xCol === '' || $yCol === '')
		{
			throw new \UnexpectedValueException(Text::_('PLG_LIST_PIVOT_ERROR_X_AND_Y_COL_MUST_BE_SELECTED'));
		}

		//pivot___date

		return array($xCol, $yCol);
	}

	/**
	 * @param $args
	 *
	 *
	 * @since 4.0
	 */
	public function onGetPluginRowHeadings(&$args)
	{
		list($xCol, $yCol) = $this->getCols();
		$args             =& $args[0];
		$yColLabel        = $args['tableHeadings'][$yCol];
		$yColHeadingClass = $args['headingClass'][$yCol];
		$yColCellClas     = $args['cellClass'][$yCol];

		$headings = array();

		$headings[$yCol] = $yColLabel;

		$data                 = $args['data'];
		$headingClass         = $args['headingClass'][$xCol];
		$cellClass            = $args['cellClass'][$xCol];
		$args['headingClass'] = array();
		$args['cellClass']    = array();

		$args['headingClass'][$yCol] = $yColHeadingClass;
		$args['cellClass'][$yCol]    = $yColCellClas;

		$group = array_shift($data);
		$row   = array_shift($group);

		foreach ($row as $k => $v)
		{
			if ($k !== $yCol)
			{
				$headings[$k]             = $k;
				$args['headingClass'][$k] = $headingClass;
				$args['cellClass'][$k]    = $cellClass;
			}
		}

		$headings['pivot_total']             = Text::_('PLG_LIST_PIVOT_LIST_X_TOTAL');
		$args['headingClass']['pivot_total'] = $headingClass;
		$args['cellClass']['pivot_total']    = $cellClass;

		$args['tableHeadings'] = $headings;
	}

	/**
	 * Set the list to use an unconstrained query in getData()
	 *
	 * @since 4.0
	 */
	public function onPreLoadData()
	{
		$params = $this->params;

		// Set the list query to be unconstrained
		$this->model->setLimits(0, -1);

		// Hide the list nav as we are running an unconstrained query
		$this->app->input->set('fabrik_show_nav', 0);
	}

	/**
	 * Try to cache the list data
	 *
	 * @since 4.0
	 */
	public function onBeforeListRender()
	{
		if (!$this->params->get('pivot_cache'))
		{
			return;
		}

		$cache = Worker::getCache();
		$cache->setCaching(1);
		$res = $cache->get(array(get_class($this), 'cacheResults'), array($this->model->getId()));

		$this->model->set('data', $res);

	}

	/**
	 * @param $listId
	 *
	 * @return array
	 *
	 * @since 4.0
	 * @throws Exception
	 */
	public static function cacheResults($listId)
	{
		/** @var ListModel $listModel */
		$listModel = FabrikModel::getInstance(ListModel::class);
		$listModel->setId($listId);
		$data = $listModel->getData();

		return $data;
	}

	/**
	 * List model has loaded its data, lets pivot it!
	 *
	 * @param array  &$args Additional options passed into the method when the plugin is called
	 *
	 * @return bool currently ignored
	 *
	 * @since 4.0
	 */
	public function onLoadData(&$args)
	{
		$data   =& $args[0]->data;
		$params = $this->getParams();
		$sums   = $params->get('pivot_sum');
		list($xCol, $yCol) = $this->getCols();
		$rawSums = $sums . '_raw';

		// Get distinct areas?
		$xCols = array();

		foreach ($data as $group)
		{
			foreach ($group as $row)
			{
				if (!in_array($row->$xCol, $xCols))
				{
					$xCols[] = $row->$xCol;
				}
			}
		}

		// Order headings
		asort($xCols);

		// Get distinct dates
		$yCols = array();

		foreach ($data as $group)
		{
			foreach ($group as $row)
			{
				if (!in_array($row->$yCol, $yCols))
				{
					$yCols[] = $row->$yCol;
				}
			}
		}

		$new = array();

		foreach ($yCols as $yColData)
		{
			$newRow        = new \stdClass();
			$newRow->$yCol = $yColData;
			$total         = 0;

			// Set default values
			foreach ($xCols as $xColData)
			{
				$newRow->$xColData = '';
			}

			foreach ($data as $group)
			{
				foreach ($group as $row)
				{
					foreach ($xCols as $xColData)
					{
						if ($row->$xCol === $xColData && $row->$yCol === $yColData)
						{
							$newRow->$xColData = $row->$sums;
							$total             += (float) $this->unNumberFormat(trim(strip_tags($row->$sums)), $params);
						}
					}
				}
			}

			$newRow->pivot_total = $total;
			$new[]               = $newRow;
		}

		/**
		 * Optionally order by the sum column. I'm sure there's some more elegant way of doing this,
		 * but for now, two usort functions will do it.
		 */
		$order = $params->get('pivot_sort', '0');

		if ($order == '1')
		{
			usort($new, function ($a, $b) {
				if ($a->pivot_total == $b->pivot_total)
				{
					return 0;
				}
				else if ($a->pivot_total > $b->pivot_total)
				{
					return -1;
				}
				else
				{
					return 1;
				}
			});
		}
		else if ($order == '2')
		{
			usort($new, function ($a, $b) {
				if ($a->pivot_total == $b->pivot_total)
				{
					return 0;
				}
				else if ($a->pivot_total < $b->pivot_total)
				{
					return -1;
				}
				else
				{
					return 1;
				}
			});
		}

		// Add totals @ bottom
		$yColTotals        = new \stdClass;
		$yColTotals->$yCol = Text::_('PLG_LIST_PIVOT_LIST_Y_TOTAL');
		$total             = 0;

		foreach ($xCols as $x)
		{
			if (!empty($x))
			{
				$c              = ArrayHelper::getColumn($new, $x);
				$yColTotals->$x = 0;

				foreach ($c as &$cc)
				{
					$cc = trim(strip_tags($cc));
					if (!empty($cc))
					{
						$yColTotals->$x += $this->unNumberFormat($cc, $params);
					}
				}

				$total += (float) $yColTotals->$x;
			}
		}

		foreach ($yColTotals as $yKey => &$y)
		{
			if ($yKey == $yCol)
			{
				continue;
			}

			$y = $this->numberFormat($y, $params);
		}

		$yColTotals->pivot_total = $total;
		$new[]                   = $yColTotals;

		foreach ($new as $newRow)
		{
			if (isset($newRow->pivot_total))
			{
				$newRow->pivot_total = $this->numberFormat($newRow->pivot_total, $params);
			}
		}

		$data[0] = $new;

		return true;
	}

	/**
	 * Format a number value
	 *
	 * @param mixed    $data (double/int)
	 * @param Registry $params
	 *
	 * @return string formatted number
	 *
	 * @since 4.0
	 */
	protected function numberFormat($data, $params)
	{
		if ($params->get('pivot_format_totals', '0') == '0')
		{
			return $data;
		}

		$decimal_length = (int) $params->get('pivot_round_to', 2);
		$decimal_sep    = $params->get('pivot_decimal_sepchar', '.');
		$thousand_sep   = $params->get('pivot_thousand_sepchar', ',');

		// Workaround for params not letting us save just a space!
		if ($thousand_sep == '#32')
		{
			$thousand_sep = ' ';
		}

		return number_format((float) $data, $decimal_length, $decimal_sep, $thousand_sep);
	}

	/**
	 * Strip number format from a number value
	 *
	 * @param   mixed $val (double/int)
	 *
	 * @return  string    formatted number
	 *
	 * @since 4.0
	 */
	public function unNumberFormat($val, $params)
	{
		if ($params->get('pivot_format_totals', '0') == '0')
		{
			return $val;
		}

		$decimal_length = (int) $params->get('pivot_round_to', 2);
		$decimal_sep    = $params->get('pivot_decimal_sepchar', '.');
		$thousand_sep   = $params->get('pivot_thousand_sepchar', ',');

		// Workaround for params not letting us save just a space!
		if ($thousand_sep == '#32')
		{
			$thousand_sep = ' ';
		}

		$val = str_replace($thousand_sep, '', $val);
		$val = str_replace($decimal_sep, '.', $val);

		return $val;
	}
}
