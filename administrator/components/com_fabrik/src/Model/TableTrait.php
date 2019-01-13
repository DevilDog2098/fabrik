<?php
/**
 * Fabrik Admin Lists Model
 *
 * @package     Joomla.Administrator
 * @subpackage  Fabrik
 * @copyright   Copyright (C) 2005-2018  Media A-Team, Inc. - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 * @since       4.0
 */

namespace Fabrik\Component\Fabrik\Administrator\Model;


use Fabrik\Helpers\Worker;
use Joomla\CMS\Table\Table;
use Fabrik\Component\Fabrik\Administrator\Table\FabrikTable;
use Joomla\Database\DatabaseDriver;

trait TableTrait
{
	/**
	 * Currently loaded list row
	 *
	 * @var Table[]
	 *
	 * @since 4.0
	 */
	private $tables = array();

	/**
	 * MVCFactory::createTable is ugly AF due to requiring a string based $prefix for namespacing.
	 *
	 * @param string $tableClass
	 * @param string $prefix
	 * @param array  $options
	 *
	 * @return Table
	 *
	 * @since version
	 */
	public function getTable($tableClass = '', $prefix = '', $options = [])
	{
		if (!class_exists($tableClass)) {
			// Try Native Joomla
			return parent::getTable($tableClass, $prefix, $options);
		}

		if (!array_key_exists($tableClass, $this->tables))
		{
			$this->tables[$tableClass] = FabrikTable::getInstance($tableClass, $prefix, $options);
		}

		return $this->tables[$tableClass];
	}

	/**
	 * Try a clean approach first then fall back to native Joomla
	 *
	 * @param        $name
	 * @param string $prefix
	 * @param array  $config
	 *
	 * @return FabrikTable
	 *
	 * @since 4.0
	 */
	protected function _createTable($name, $prefix = 'Table', $config = array())
	{
		$tableClass = "Joomla\\Component\\Fabrik\\Administrator\\Table\\".ucfirst($name)."Table";

		if (class_exists($tableClass)) {
			return FabrikTable::getInstance($tableClass, $prefix, $config);
		}

		return parent::_createTable($name, $prefix, $config);
	}
}