<?php
/**
 * Package
 *
 * @package     Joomla
 * @subpackage  Fabrik
 * @copyright   Copyright (C) 2005-2016  Media A-Team, Inc. - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

namespace Fabrik\Component\Fabrik\Site\Model;

// No direct access
defined('_JEXEC') or die('Restricted access');

use Fabrik\Component\Fabrik\Administrator\Model\FabrikModel;
use Fabrik\Component\Fabrik\Administrator\Table\FabrikTable;
use Fabrik\Component\Fabrik\Administrator\Table\PackageTable;
use Fabrik\Helpers\Worker;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\Registry\Registry;

/**
 * Package
 *
 * @package     Joomla
 * @subpackage  Fabrik
 * @since       4.0
 */
class PackageModel extends FabrikSiteModel
{
	/**
	 * table objects
	 *
	 * @var array
	 *
	 * @since 4.0
	 */
	protected $tables = array();

	/**
	 * Package items
	 *
	 * @var PackageTable
	 *
	 * @since 4.0
	 */
	private $package = null;

	/**
	 * ID
	 *
	 * @var int id
	 *
	 * @since 4.0
	 */
	public $id = null;

	/**
	 * Method to set the  id
	 *
	 * @param   int  $id  ID number
	 *
	 * @return  void
	 *              
	 * @since 4.0
	 */
	public function setId($id)
	{
		// Set new package ID
		$this->id = $id;
	}

	/**
	 * Method to auto-populate the model state.
	 *
	 * Note. Calling getState in this method will result in recursion.
	 *
	 * @since	1.6
	 *
	 * @return  void
	 */
	protected function populateState()
	{
		$app = Factory::getApplication('site');

		// Load the parameters.
		$params = $app->getParams();

		// Load state from the request.
		$pk = $app->input->getInt('id', $params->get('id'));
		$this->setState('package.id', $pk);

		$this->setState('params', $params);

		// TODO: Tune these values based on other permissions.
		$user = Factory::getUser();

		if ((!$user->authorise('core.edit.state', 'com_fabrik')) && (!$user->authorise('core.edit', 'com_fabrik')))
		{
			$this->setState('filter.published', 1);
			$this->setState('filter.archived', 2);
		}
	}

	/**
	 * Method to get package data.
	 * Packages are all stored in jos_fabrik_packages - so don't use {package} in the query to load them
	 *
	 * @param   int  $pk  The id of the package.
	 *
	 * @return  mixed	Menu item data object on success, false on failure.
	 *                
	 * @since 4.0
	 */
	public function &getItem($pk = null)
	{
		// Initialise variables.
		$pk = (!empty($pk)) ? $pk : (int) $this->getState('package.id');

		if (!isset($this->_item))
		{
			$this->_item = array();
		}

		if (!isset($this->_item[$pk]))
		{
			try
			{
				$db = Worker::getDbo();
				$query = $db->getQuery(true);

				$query->select('label, params, published, component_name');
				$query->from('#__fabrik_packages');

				$query->where('id = ' . (int) $pk);

				// Filter by published state.
				$published = $this->getState('filter.published');
				$archived = $this->getState('filter.archived');

				if (is_numeric($published))
				{
					$query->where('(published = ' . (int) $published . ' OR published =' . (int) $archived . ')');
				}

				$db->setQuery($query);
				$data = $db->loadObject();

				if ($error = $db->getErrorMsg())
				{
					throw new \Exception($error);
				}

				if (empty($data))
				{
					throw new \RuntimeException(Text::_('COM_FABRIK_ERROR_PACKAGE_NOT_FOUND'), 404);
				}

				// Check for published state if filter set.
				if (((is_numeric($published)) || (is_numeric($archived))) && (($data->published != $published) && ($data->published != $archived)))
				{
					throw new \RuntimeException(Text::_('COM_FABRIK_ERROR_PACKAGE_NOT_FOUND'), 404);
				}

				// Convert parameter fields to objects.
				$registry = new Registry;
				$registry->loadJSON($data->params);
				$data->params = clone $this->getState('params');
				$data->params->merge($registry);

				$this->_item[$pk] = $data;
			}
			catch (\RuntimeException $e)
			{
				$this->setError($e);
				$this->_item[$pk] = false;
			}
		}

		return $this->_item[$pk];
	}

	/**
	 * get a package table object
	 *
	 * @return  PackageTable connection tables
	 *
	 * @since 4.0
	 */
	public function getPackage()
	{
		if (!isset($this->package))
		{
			$this->package = FabrikTable::getInstance(PackageTable::class);
			$this->package->load($this->id);

			// Forms can currently only be set from form module
			$this->package->forms = '';
		}

		return $this->package;
	}

	/**
	 * Render the package in the front end
	 *
	 * @return  void
	 *
	 * @since 4.0
	 */
	public function render()
	{
		// Test stuff needs to be assigned in admin
		$this->_blocks = array();

		return;
	}

	/**
	 * Load in the tables associated with the package
	 *
	 * @return  array
	 *
	 * @since 4.0
	 */
	protected function loadTables()
	{
		if ($this->package->tables != '')
		{
			$aIds = explode(',', $this->package->tables);

			foreach ($aIds as $id)
			{
				// @todo this doesn't exist 4.0
				$viewModel = FabrikModel::getInstance('view', 'FabrikFEModel');
				$viewModel->setId($id);
				$this->tables[] = $viewModel->getTable();
				$formModel = $viewModel->getFormModel();
				$this->forms[] = $formModel->getForm();
			}
		}

		return $this->tables;
	}

	/**
	 * (un)publish the package & all its tables
	 *
	 * @param   int  $state  State
	 *
	 * @return  void
	 *
	 * @since 4.0
	 */
	public function publish($state)
	{
		foreach ($this->tables as $oTable)
		{
			$oTable->publish($oTable->id, $state);
		}

		parent::publish($this->id, $state);
	}
}

///**
// * Package Menu
// *
// * @package     Joomla
// * @subpackage  Fabrik
// * @since       3.0
// */
//
//class FabrikPackageMenu extends JModelLegacy
//{
//	/**
//	 * Method to set the  id
//	 *
//	 * @param   int  $id  ID number
//	 *
//	 * @return  void
//	 */
//	public function setId($id)
//	{
//		// Set new form ID
//		$this->id = $id;
//	}
//
//	/**
//	 * Render
//	 *
//	 * @return string
//	 */
//	public function render()
//	{
//		return "menu items to go here";
//	}
//}