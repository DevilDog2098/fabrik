<?php
/**
 * Fabrik Admin Form Model
 *
 * @package     Joomla.Administrator
 * @subpackage  Fabrik
 * @copyright   Copyright (C) 2005-2016  Media A-Team, Inc. - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 * @since       1.6
 */

namespace Fabrik\Component\Fabrik\Administrator\Model;

// No direct access
defined('_JEXEC') or die('Restricted access');

use Fabrik\Helpers\Worker;
use Fabrik\Helpers\ArrayHelper as FArrayHelper;
use Fabrik\Helpers\StringHelper as FStringHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Table\Table;
use Fabrik\Component\Fabrik\Administrator\Table\FabrikTable;
use Fabrik\Component\Fabrik\Administrator\Table\FormGroupTable;
use Fabrik\Component\Fabrik\Administrator\Table\FormTable;
use Fabrik\Component\Fabrik\Administrator\Table\ListTable;
use Joomla\Utilities\ArrayHelper;

/**
 * Fabrik Admin Form Model
 *
 * @package     Joomla.Administrator
 * @subpackage  Fabrik
 * @since       4.0
 */
class FormModel extends FabrikAdminModel
{
	/**
	 * The prefix to use with controller messages.
	 *
	 * @var  string
	 *
	 * @since 4.0
	 */
	protected $text_prefix = 'COM_FABRIK_FORM';

	/**
	 * @var ContentTypeImportModel
	 *
	 * @since 4.0
	 */
	protected $contentTypeModel;

	/**
	 * Returns a reference to the a Table object, always creating it.
	 *
	 * @param   string $type   The table type to instantiate
	 * @param   string $prefix A prefix for the table class name. Optional.
	 * @param   array  $config Configuration array for model. Optional.
	 *
	 * @return  Table    A database object
	 *
	 * @since    1.6
	 */
	public function getTable($type = FormTable::class, $prefix = '', $config = array())
	{
		$config['dbo'] = Worker::getDbo(true);

		return FabrikTable::getInstance($type, '', $config);
	}

	/**
	 * Method to get the record form.
	 *
	 * @param   array $data     Data for the form.
	 * @param   bool  $loadData True if the form is to load its own data (default case), false if not.
	 *
	 * @return  mixed  A JForm object on success, false on failure
	 *
	 * @since    1.6
	 */
	public function getForm($data = array(), $loadData = true)
	{
		// Get the form.
		$form = $this->loadForm('com_fabrik.form', 'form', array('control' => 'jform', 'load_data' => $loadData));

		if (empty($form))
		{
			return false;
		}

		$form->model = $this;

		return $form;
	}

	/**
	 * Method to get the data that should be injected in the form.
	 *
	 * @return  mixed    The data for the form.
	 *
	 * @since    1.6
	 */
	protected function loadFormData()
	{
		// Check the session for previously entered form data.
		$data = $this->app->getUserState('com_fabrik.edit.form.data', array());

		if (empty($data))
		{
			$data = $this->getItem();
		}

		return $data;
	}

	/**
	 * Get JS
	 *
	 * @return string
	 *
	 * @since 4.0
	 */
	public function getJs()
	{
		$js[]    = "\twindow.addEvent('domready', function () {";
		$plugins = json_encode($this->getPlugins());
		$js[]    = "\t\tFabrik.controller = new PluginManager($plugins, " . (int) $this->getItem()->id . ", 'form');";
		$js[]    = "\t})";

		return implode("\n", $js);
	}

	/**
	 * Save the form
	 *
	 * @param   array $data posted jForm data
	 *
	 * @return  bool
	 *
	 * @since 4.0
	 */
	public function save($data)
	{
		$input                                = $this->app->input;
		$jForm                                = $input->get('jform', array(), 'array');
		$data['params']['plugins']            = array_values((array) FArrayHelper::getValue($jForm, 'plugin'));
		$data['params']['plugin_locations']   = array_values((array) FArrayHelper::getValue($jForm, 'plugin_locations'));
		$data['params']['plugin_events']      = array_values((array) FArrayHelper::getValue($jForm, 'plugin_events'));
		$data['params']['plugin_description'] = array_values((array) FArrayHelper::getValue($jForm, 'plugin_description'));

		/**
		 * Move back into the main data array some values we are rendering as
		 * params (did that for ease of rendering admin output)
		 */
		$opts = array('reset_button_label', 'submit_button_label');

		foreach ($opts as $opt)
		{
			$data[$opt] = $data['params'][$opt];
		}

		$tmpName = FArrayHelper::getValue($data, 'db_table_name');
		unset($data['db_table_name']);
		$return = parent::save($data);

		if ($return)
		{
			$data['db_table_name'] = $tmpName;
			$this->saveFormGroups($data);
		}

		parent::cleanCache('com_fabrik');

		return $return;
	}

	/**
	 * After having saved the form we
	 * 1) Create a new group if none selected in edit form list
	 * 2) Delete all old form_group records
	 * 3) Recreate the form group records
	 * 4) Make a list view if needed
	 *
	 * @param   array $data jForm data
	 *
	 * @throws \Exception
	 *
	 * @return  bool  True if you should display the form list, False if you're
	 * redirected elsewhere
	 *
	 * @since 4.0
	 */
	public function saveFormGroups($data)
	{
		// These are set in parent::save() and contain the updated form id and if the form is a new form
		$formId = (int) $this->getState($this->getName() . '.id');
		$isNew  = (bool) $this->getState($this->getName() . '.new');

		/** @var ListModel $listModel */
		$listModel = FabrikModel::getInstance(ListModel::class);
		$item      = $listModel->loadFromFormId($formId);

		$listModel->set('form.id', $formId);
		$listModel->setState('list.form_id', $formId);
		$recordInDatabase = $data['record_in_database'];
		$dbTableName      = $this->safeTableName($isNew, $data, $item);
		$fields           = $this->getInsertFields($isNew, $data, $listModel, $dbTableName);

		if ($recordInDatabase != '1')
		{
			return;
		}

		$dbTableExists = $listModel->databaseTableExists($dbTableName);

		if (!$dbTableExists)
		{
			$listModel->createDBTable($dbTableName, $fields);
		}

		if (!$dbTableExists || $isNew)
		{
			$connection = Worker::getConnection(-1);
			$item->set('id', null);
			$item->set('label', $data['label']);
			$item->set('form_id', $formId);
			$item->set('connection_id', $connection->getConnection()->id);
			$item->set('db_table_name', $dbTableName);

			// Store key without quoteNames as that is db specific which we no longer want
			$item->set('db_primary_key', $dbTableName . '.id');
			$item->set('auto_inc', 1);
			$item->set('published', $data['published']);
			$item->set('created', $data['created']);
			$item->set('created_by', $data['created_by']);
			$item->set('access', 1);
			$item->set('params', $listModel->getDefaultParams());
			$item->check();
			$item->store();

			$this->contentTypeModel->finalise($item);
		}
		else
		{
			// Update existing table (seems to need to reload here to ensure that _table is set)
			$listModel->loadFromFormId($formId);
			$listModel->ammendTable();
			$currentGroups = (array) FArrayHelper::getValue($data, 'current_groups');
			$this->_makeFormGroups($currentGroups);
		}
	}

	/**
	 * @param bool                 $isNew
	 * @param array                $data
	 * @param ListModel $listModel
	 *
	 * @throws \Exception
	 *
	 * @return array
	 *
	 * @since 4.0
	 */
	private function getInsertFields($isNew, $data, ListModel $listModel, $dbTableName)
	{
		$db                     = Worker::getDbo(true);
		$fields                 = array('id' => 'internalid', 'date_time' => 'date');
		$createGroup            = $data['_createGroup'];
		$recordInDatabase       = $data['record_in_database'];
		$jForm                  = $this->app->input->get('jform', array(), 'array');
		$this->contentTypeModel = FabrikModel::getInstance(ContentTypeImportModel::class, '', array('listModel' => $listModel));
		$groups                 = FArrayHelper::getValue($data, 'current_groups');
		$contentType            = ArrayHelper::getValue($jForm, 'contenttype');

		if ($createGroup)
		{
			$this->contentTypeModel->check($contentType);
		}

		if (empty($groups) && !$isNew)
		{
			throw new \Exception(Text::_('COM_FABRIK_ERR_ONE_GROUP_MUST_BE_SELECTED'));
		}

		// If new and record in db and group selected then we want to get those groups elements to create fields for in the db table
		if ($isNew && $recordInDatabase)
		{
			if (!empty($groups))
			{
				$query = $db->getQuery(true);
				$query->select('plugin, name')->from('#__fabrik_elements')
					->where('group_id IN (' . implode(',', $groups) . ')');
				$db->setQuery($query);
				$rows = $db->loadObjectList();

				foreach ($rows as $row)
				{
					$fields[$row->name] = $row->plugin;
				}

				$this->_makeFormGroups($groups);
			}
		}

		if ($createGroup)
		{
			$fields = $this->contentTypeModel->import($contentType, $dbTableName);
		}

		return $fields;
	}

	/**
	 * Create a safe table name from the input
	 *
	 * @param   bool            $isNew
	 * @param   array           $data
	 * @param   ListTable $item
	 *
	 * @return string
	 *
	 * @since 4.0
	 */
	private function safeTableName($isNew, $data, ListTable $item)
	{
		if ($isNew)
		{
			$dbTableName = $data['db_table_name'] !== '' ? $data['db_table_name'] : $data['label'];

			// Mysql will force db table names to lower case even if you set the db name to upper case - so use clean()
			$dbTableName = FStringHelper::clean($dbTableName);

			// Otherwise part of the table name is taken for element names
			$dbTableName = str_replace('___', '_', $dbTableName);
		}
		else
		{
			$dbTableName = $item->get('db_table_name', '') == '' ? $data['database_name'] : $item->get('db_table_name');
		}

		return preg_replace('#[^0-9a-zA-Z_]#', '', $dbTableName);
	}

	/**
	 * Reinsert the groups ids into form group rows
	 *
	 * @param   array $currentGroups group ids
	 *
	 * @return  void
	 *
	 * @since 4.0
	 */
	protected function _makeFormGroups($currentGroups)
	{
		$formId        = $this->getState($this->getName() . '.id');
		$db            = Worker::getDbo(true);
		$query         = $db->getQuery(true);
		$currentGroups = ArrayHelper::toInteger($currentGroups);
		$query->delete('#__{package}_formgroup')->where('form_id = ' . (int) $formId);

		if (!empty($currentGroups))
		{
			$query->where('group_id NOT IN (' . implode($currentGroups, ', ') . ')');
		}

		$db->setQuery($query);

		// Delete the old form groups
		$db->execute();

		// Get previously saved form groups
		$query->clear()->select('id, group_id')->from('#__{package}_formgroup')->where('form_id = ' . (int) $formId);
		$db->setQuery($query);
		$formGroupIds  = $db->loadObjectList('group_id');
		$orderId       = 1;
		$currentGroups = array_unique($currentGroups);

		foreach ($currentGroups as $group_id)
		{
			if ($group_id != '')
			{
				$group_id = (int) $group_id;
				$query->clear();

				if (array_key_exists($group_id, $formGroupIds))
				{
					$query->update('#__{package}_formgroup')
						->set('ordering = ' . $orderId)->where('id =' . $formGroupIds[$group_id]->id);
				}
				else
				{
					$query->insert('#__{package}_formgroup')
						->set(array('form_id =' . (int) $formId, 'group_id = ' . $group_id, 'ordering = ' . $orderId));
				}

				$db->setQuery($query);
				$db->execute();
				$orderId++;
			}
		}
	}

	/**
	 * Take an array of list ids and return the corresponding form_id's
	 * used in list publish code
	 *
	 * @param   array $ids list ids
	 *
	 * @return array form ids
	 *
	 * @since 4.0
	 */
	public function swapListToFormIds($ids = array())
	{
		if (empty($ids))
		{
			return array();
		}

		$ids   = ArrayHelper::toInteger($ids);
		$db    = Worker::getDbo(true);
		$query = $db->getQuery(true);
		$query->select('form_id')->from('#__{package}_lists')->where('id IN (' . implode(',', $ids) . ')');

		return $db->setQuery($query)->loadColumn();
	}

	/**
	 * Iterate over the form's elements and update its db table to match
	 *
	 * @return  void
	 *
	 * @since 4.0
	 */
	public function updateDatabase()
	{
		$input  = $this->app->input;
		$cid    = $input->get('cid', array(), 'array');
		$formId = $cid[0];
		$model  = FabrikModel::getInstance(\Fabrik\Component\Fabrik\Site\Model\FormModel::class);
		$model->setId($formId);
		$form = $model->getForm();

		// Use this in case there is not table view linked to the form
		if ($form->record_in_database == 1)
		{
			// There is a list view linked to the form so lets load it
			$listModel = FabrikModel::getInstance(ListModel::class);
			$listModel->loadFromFormId($formId);
			$listModel->setFormModel($model);
			$dbExists = $listModel->databaseTableExists();

			if (!$dbExists)
			{
				/* $$$ hugh - if we're recreating a table for an existing form, we need to pass the field
				 * list to createDBTable(), otherwise all we get is id and date_time.  Not sure if this
				 * code really belongs here, or if we should handle it in createDBTable(), but I didn't want
				 * to mess with createDBTable(), although I did have to make one small change in it (see comments
				 * therein).
				 * NOTE 1 - this code ignores joined groups, so only recreates the original table
				 * NOTE 2 - this code ignores any 'alter existing fields' settings.
				 */
				$db    = Worker::getDbo(true);
				$query = $db->getQuery(true);
				$query->select('group_id')->from('#__{package}_formgroup AS fg')->join('LEFT', '#__{package}_groups AS g ON g.id = fg.group_id')
					->where('fg.form_id = ' . $formId . ' AND g.is_join != 1');
				$db->setQuery($query);
				$groupIds = $db->loadColumn();

				if (!empty($groupIds))
				{
					$fields = array();
					$query  = $db->getQuery(true);
					$query->select('plugin, name')->from('#__fabrik_elements')->where('group_id IN (' . implode(',', $groupIds) . ')');
					$db->setQuery($query);
					$rows = $db->loadObjectList();

					foreach ($rows as $row)
					{
						$fields[$row->name] = $row->plugin;
					}

					if (!empty($fields))
					{
						$listModel->createDBTable($listModel->getTable()->db_table_name, $fields);
					}
				}
			}
			else
			{
				$listModel->ammendTable();
			}
		}
	}

	/**
	 * Method to validate the form data.
	 *
	 * @param   JForm  $form  The form to validate against.
	 * @param   array  $data  The data to validate.
	 * @param   string $group The name of the field group to validate.
	 *
	 * @return  mixed    Array of filtered data if valid, false otherwise.
	 *
	 * @since    1.1
	 */
	public function validate($form, $data, $group = null)
	{
		$params = $data['params'];
		$ok     = parent::validate($form, $data);

		// Standard jForm validation failed so we shouldn't test further as we can't be sure of the data
		if (!$ok)
		{
			return false;
		}

		// Hack - must be able to add the plugin xml fields file to $form to include in validation but cant see how at the moment
		$data['params'] = $params;

		return $data;
	}

	/**
	 * Delete form and form groups
	 *
	 * @param   array &$ids to delete
	 *
	 * @return  bool
	 *
	 * @since 4.0
	 */
	public function delete(&$ids)
	{
		$res = parent::delete($ids);

		if ($res)
		{
			foreach ($ids as $cid)
			{
				$item = FabrikTable::getInstance(FormGroupTable::class);
				$item->load(array('form_id' => $cid));
				$item->delete();
			}
		}

		return $res;
	}
}
