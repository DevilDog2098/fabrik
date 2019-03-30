<?php
/**
 * List controller class.
 *
 * @package     Joomla.Administrator
 * @subpackage  Fabrik
 * @copyright   Copyright (C) 2005-2018  Media A-Team, Inc. - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 * @since       4.0
 */

namespace Fabrik\Component\Fabrik\Administrator\Controller;

// No direct access
defined('_JEXEC') or die('Restricted access');

use Fabrik\Component\Fabrik\Administrator\Dispatcher\Dispatcher;
use Joomla\CMS\Application\CMSApplication;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Factory;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Session\Session;
use Joomla\CMS\Toolbar\ToolbarHelper;
use Joomla\CMS\Uri\Uri;
use Fabrik\Component\Fabrik\Administrator\Helper\FabrikAdminHelper;
use Fabrik\Component\Fabrik\Administrator\Model\ConnectionsModel;
use Fabrik\Component\Fabrik\Administrator\Model\FabrikModel;
use Fabrik\Component\Fabrik\Administrator\Model\ListModel as AdminListModel;
use Fabrik\Component\Fabrik\Administrator\View\ListView\HtmlView;
use Fabrik\Component\Fabrik\Site\Model\ListModel;
use Joomla\Utilities\ArrayHelper;
use Fabrik\Helpers\Html;
use Fabrik\Helpers\Worker;

/**
 * Admin List controller class.
 *
 * @package     Joomla.Administrator
 * @subpackage  Fabrik
 * @since       4.0
 */
class ListController extends AbstractFormController
{
	/**
	 * The prefix to use with controller messages.
	 *
	 * @var    string
	 *
	 * @since 4.0
	 */
	protected $text_prefix = 'COM_FABRIK_LIST';

	/**
	 * Used from content plugin when caching turned on to ensure correct element rendered)
	 *
	 * @var int
	 *
	 * @since 4.0
	 */
	protected $cacheId = 0;

	/**
	 * @var string
	 *
	 * @since 4.0
	 */
	protected $view_item = 'list';

	/**
	 * @var string
	 *
	 * @since 4.0
	 */
	protected $view_list = 'lists';

	/**
	 * Method to edit an existing record.
	 *
	 * @param   string $key    The name of the primary key of the URL variable.
	 * @param   string $urlVar The name of the URL variable if different from the primary key
	 *                         (sometimes required to avoid router collisions).
	 *
	 * @return  boolean  True if access level check and checkout passes, false otherwise.
	 *
	 * @since 4.0
	 */
	public function edit($key = null, $urlVar = null)
	{
		/** @var ConnectionsModel $model */
		$model = $this->getModel(ConnectionsModel::class);

		if (count($model->activeConnections()) == 0)
		{
			throw new \RuntimeException(Text::_('COM_FABRIK_ENUSRE_ONE_CONNECTION_PUBLISHED'));
		}

		parent::edit($key, $urlVar);
	}

	/**
	 * Set up a confirmation screen asking about renaming the list you want to copy
	 *
	 * @throws \Exception
	 *
	 * @return mixed notice or null
	 *
	 * @since 4.0
	 */
	public function copy()
	{
		$input = $this->input;
		$cid   = $input->get('cid', array(0), 'array');
		/** @var ListModel $model */
		$model = FabrikModel::getInstance(ListModel::class);

		if (count($cid) > 0)
		{
			$viewType = Factory::getDocument()->getType();
			$view     = $this->getView('listView', $viewType, '');
			$view->setModel($model, true);
			$view->confirmCopy('confirm_copy');
		}
		else
		{
			throw new \Exception(Text::_('NO ITEMS SELECTED'));
		}
	}

	/**
	 * Actually copy the list
	 *
	 * @return  null
	 *
	 * @since 4.0
	 */
	public function doCopy()
	{
		// Check for request forgeries
		Session::checkToken() or die('Invalid Token');
		$input = $this->input;
		$model = $this->getModel();
		$model->copy();
		$nText = $this->text_prefix . '_N_ITEMS_COPIED';
		$this->setMessage(Text::plural($nText, count($input->get('cid', array(), 'array'))));
		$this->setRedirect(Route::_('index.php?option=' . $this->option . '&view=' . $this->view_list, false));
	}

	/**
	 * Show the lists data in the admin
	 *
	 * @param   object $model list model
	 *
	 * @return  null
	 *
	 * @since 4.0
	 */
	public function view($model = null)
	{
		$input = $this->input;
		$cid   = $input->get('cid', array(0), 'array');
		$cid   = $cid[0];

		if (is_null($model))
		{
			$cid = $input->getInt('listid', $cid);

			// Grab the model and set its id
			$model = FabrikModel::getInstance(ListModel::class);
			$model->setState('list.id', $cid);
		}

		/** @var CMSApplication $app */
		$app = Factory::getApplication();
		$viewType = $app->getDocument()->getType();

		// Use the front end renderer to show the table
		$viewLayout = $input->getWord('layout', 'default');
		$view       = $this->getView('listView', $viewType, Dispatcher::PREFIX_SITE);
		$view->setModel($model, true);

		// Set the layout
		$view->setLayout($viewLayout);
		ToolbarHelper::title(Text::_('COM_FABRIK_MANAGER_LISTS'), 'list');

		// Build unique cache id on url, post and user id
		$user    = Factory::getUser();
		$uri     = Uri::getInstance();
		$uri     = $uri->toString(array('path', 'query'));
		$cacheId = serialize(array($uri, $input->post, $user->get('id'), get_class($view), 'display', $this->cacheId));
		$cache   = Factory::getCache('com_fabrik', 'view');

		if (!Worker::useCache($model))
		{
			$view->display();
		}
		else
		{
			$cache->get($view, 'display', $cacheId);
			Html::addToSessionCacheIds($cacheId);
		}

		FabrikAdminHelper::addSubmenu($input->getWord('view', 'lists'));
	}

	/**
	 * Show the elements associated with the list
	 *
	 * @return  void
	 *
	 * @since 4.0
	 */
	public function showLinkedElements()
	{
		$document = Factory::getDocument();
		$input    = $this->input;
		$cid      = $input->get('cid', array(0), 'array');
		$model    = FabrikModel::getInstance(ListModel::class);
		$model->setState('list.id', $cid[0]);
		$formModel  = $model->getFormModel();
		$viewType   = $document->getType();
		$viewLayout = $input->getWord('layout', 'linked_elements');
		$view       = $this->getView('listView', $viewType, '');
		$view->setModel($model, true);
		$view->setModel($formModel);

		// Set the layout
		$view->setLayout($viewLayout);
		$view->showLinkedElements();
	}

	/**
	 * Order the lists
	 *
	 * @return  null
	 *
	 * @since 4.0
	 */
	public function order()
	{
		// Check for request forgeries
		Session::checkToken() or die('Invalid Token');
		$input = $this->input;
		$model = FabrikModel::getInstance(ListModel::class);
		$id    = $input->getInt('listid');
		$model->setId($id);
		$input->set('cid', $id);
		$model->setOrderByAndDir();

		// $$$ hugh - unset 'resetfilters' in case it was set on QS of original table load.
		$input->set('resetfilters', 0);
		$input->set('clearfilters', 0);
		$this->view();
	}

	/**
	 * Clear filters
	 *
	 * @return  null
	 *
	 * @since 4.0
	 */
	public function clearfilter()
	{
		$this->app->enqueueMessage(Text::_('COM_FABRIK_FILTERS_CLEARED'));
		$this->filter();
	}

	/**
	 * Filter the list data
	 *
	 * @return  void
	 *
	 * @since 4.0
	 */
	public function filter()
	{
		// Check for request forgeries
		Session::checkToken() or die('Invalid Token');
		$input = $this->input;
		$model = FabrikModel::getInstance(ListModel::class);
		$id    = $input->get('listid');
		$model->setId($id);
		$input->set('cid', $id);
		$request = $model->getRequestData();
		$model->storeRequestData($request);

		// $$$ rob pass in the model otherwise display() rebuilds it and the request data is rebuilt
		$this->view($model);
	}

	/**
	 * Delete rows from table
	 *
	 * @return  null
	 *
	 * @since 4.0
	 */
	public function delete()
	{
		// Check for request forgeries
		Session::checkToken() or die('Invalid Token');
		$input  = $this->input;
		$model  = FabrikModel::getInstance(ListModel::class);
		$listId = $input->getInt('listid');
		$model->setId($listId);
		$ids        = $input->get('ids', array(), 'array');
		$limitStart = $input->getInt('limitstart' . $listId);
		$length     = $input->getInt('limit' . $listId);
		$oldTotal   = $model->getTotalRecords();
		$ok         = $model->deleteRows($ids);
		$total      = $oldTotal - count($ids);
		$ref        = 'index.php?option=com_fabrik&task=list.view&cid=' . $listId;

		if ($total >= $limitStart)
		{
			$newLimitStart = $limitStart - $length;

			if ($newLimitStart < 0)
			{
				$newLimitStart = 0;
			}

			$ref     = str_replace('limitstart' . $listId . '=' . $limitStart, 'limitstart' . $listId . '=' . $newLimitStart, $ref);
			$context = 'com_fabrik.list' . $model->getRenderContext() . '.list.';
			$this->app->setUserState($context . 'limitstart' . $listId, $newLimitStart);
		}

		if ($input->get('format') == 'raw')
		{
			$input->set('view', 'list');
			$this->view();
		}
		else
		{
			$msg = $ok ? count($ids) . ' ' . Text::_('COM_FABRIK_RECORDS_DELETED') : '';
			$this->app->enqueueMessage($msg);
			$this->app->redirect($ref);
		}
	}

	/**
	 * Empty a table of records and reset its key to 0
	 *
	 * @return  null
	 *
	 * @since 4.0
	 */
	public function doEmpty()
	{
		$model = $this->getModel(ListModel::class);
		$input = $this->input;
		$model->truncate();
		$listId = $input->getInt('listid');
		$ref    = $input->get('fabrik_referrer', 'index.php?option=com_fabrik&view=list&cid=' . $listId, 'string');
		$this->setRedirect($ref);
	}

	/**
	 * Run a list plugin
	 *
	 * @return  null
	 *
	 * @since 4.0
	 */
	public function doPlugin()
	{
		$input = $this->input;
		$cid   = $input->get('cid', array(0), 'array');
		$cid   = $cid[0];
		$model = $this->getModel(ListModel::class);
		$model->setId($input->getInt('listid', $cid));

		// $$$ rob need to ask the model to get its data here as if the plugin calls $model->getData
		// then the other plugins are recalled which makes the current plugins params incorrect.
		$model->setLimits();
		$model->getData();

		// If showing n tables in article page then ensure that only activated table runs its plugin
		if ($input->getInt('id') == $model->get('id') || $input->get('origid', '', 'string') == '')
		{
			$messages = $model->processPlugin();

			if ($input->get('format') == 'raw')
			{
				$input->set('view', 'list');
			}
			else
			{
				foreach ($messages as $msg)
				{
					$this->app->enqueueMessage($msg);
				}
			}
		}

		$format = $input->get('format', 'html');
		$ref    = 'index.php?option=com_fabrik&task=list.view&listid=' . $model->getId() . '&format=' . $format;
		$this->app->redirect($ref);
	}

	/**
	 * Method to save a record or if a new list show the 'select content type' form.
	 *
	 * @param   string $key    The name of the primary key of the URL variable.
	 * @param   string $urlVar The name of the URL variable if different from the primary key (sometimes required to
	 *                         avoid router collisions).
	 *
	 * @return  boolean  True if successful, false otherwise.
	 *
	 * @since 4.0
	 */
	public function save($key = null, $urlVar = null)
	{
		$data = (array) $this->input->post->get('jform', array(), 'array');

		if ((int) $data['id'] === 0 && ArrayHelper::getValue($data, 'db_table_name', '') === '')
		{
			$viewType = Factory::getDocument()->getType();
			$model    = FabrikModel::getInstance(AdminListModel::class);
			/** @var HtmlView $view */
			$view = $this->getView('listView', $viewType, '');
			$view->setModel($model, true);
			$view->selectContentType('select_content_type');

			return true;
		}

		parent::save($key, $urlVar);
	}

	/**
	 * Method to always save a list.
	 *
	 * @param   string $key    The name of the primary key of the URL variable.
	 * @param   string $urlVar The name of the URL variable if different from the primary key (sometimes required to
	 *                         avoid router collisions).
	 *
	 * @return  boolean  True if successful, false otherwise.
	 *
	 * @since 4.0
	 */
	public function doSave($key = null, $urlVar = null)
	{
		return parent::save($key, $urlVar);
	}
}