<?php
/**
 * Cron list controller class.
 *
 * @package     Joomla.Administrator
 * @subpackage  Fabrik
 * @copyright   Copyright (C) 2005-2016  Media A-Team, Inc. - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 * @since       3.0
 */

namespace Joomla\Component\Fabrik\Administrator\Controller;

// No direct access
defined('_JEXEC') or die('Restricted access');

use Fabrik\Helpers\Worker;
use Joomla\CMS\Application\CMSApplication;
use Joomla\CMS\Factory;
use Joomla\Component\Fabrik\Administrator\Model\CronModel;
use Joomla\Component\Fabrik\Administrator\Model\FabrikModel;
use Joomla\Component\Fabrik\Administrator\Model\ListModel;
use Joomla\Component\Fabrik\Administrator\Table\CronTable;
use Joomla\Component\Fabrik\Administrator\Table\FabrikTable;
use Joomla\Component\Fabrik\Administrator\Table\LogTable;
use Joomla\Component\Fabrik\Site\Model\PluginManagerModel;
use Joomla\Component\Fabrik\Site\Model\ListModel as SiteListModel;
use Joomla\Utilities\ArrayHelper;

/**
 * Cron list controller class.
 *
 * @package     Joomla.Administrator
 * @subpackage  Fabrik
 * @since       4.0
 */
class CronsController extends AbstractAdminController
{
	/**
	 * The prefix to use with controller messages.
	 *
	 * @var    string
	 *
	 * @since 4.0
	 */
	protected $text_prefix = 'COM_FABRIK_CRONS';

	/**
	 * View item name
	 *
	 * @var string
	 *
	 * @since 4.0
	 */
	protected $view_item = 'crons';

	/**
	 * @var null
	 *
	 * @since 4.0
	 */
	protected $runningId = null;

	/**
	 * Proxy for getModel.
	 *
	 * @param   string $name   model name
	 * @param   string $prefix model prefix
	 * @param   array  $config Configuration array for model. Optional.
	 *
	 * @return  CronModel
	 *
	 * @since 4.0
	 */
	public function getModel($name = CronModel::class, $prefix = '', $config = array())
	{
		$model = parent::getModel($name, $prefix, array('ignore_request' => true));

		return $model;
	}

	/**
	 * @since 4.0
	 */
	public function shutdownHandler()
	{
		$id = $this->runningId;
		if (@is_array($e = @error_get_last()))
		{
			$code = isset($e['type']) ? $e['type'] : 0;
			$msg  = isset($e['message']) ? $e['message'] : '';
			$file = isset($e['file']) ? $e['file'] : '';
			$line = isset($e['line']) ? $e['line'] : '';
			if ($code > 0)
			{
				$this->log->message = "$code,$msg,$file,$line";
				$this->log->store();
			}
		}
	}


	/**
	 * Run the selected cron plugins
	 *
	 * @return  void
	 *
	 * @since 4.0
	 */
	public function run()
	{
		/** @var CMSApplication $app */
		$app    = Factory::getApplication();
		$mailer = Factory::getMailer();
		$config = $app->getConfig();
		$db     = Worker::getDbo(true);
		$input  = $app->input;
		$cid    = $input->get('cid', array(), 'array');
		$cid    = ArrayHelper::toInteger($cid);
		$cid    = implode(',', $cid);
		$query  = $db->getQuery(true);
		$query->select('*')->from('#__{package}_cron')->where('id IN (' . $cid . ')');
		$db->setQuery($query);
		$rows           = $db->loadObjectList();
		$adminListModel = FabrikModel::getInstance(ListModel::class);
		$pluginManager  = FabrikModel::getInstance(PluginManagerModel::class);
		$listModel      = FabrikModel::getInstance(SiteListModel::class);
		$c              = 0;
		$this->log      = FabrikTable::getInstance(LogTable::class);

		register_shutdown_function(array($this, 'shutdownHandler'));

		foreach ($rows as $row)
		{
			// Load in the plugin
			$rowParams                = json_decode($row->params);
			$this->log->message       = '';
			$this->log->id            = null;
			$this->log->referring_url = '';
			$this->log->message_type  = 'plg.cron.' . $row->plugin;
			$plugin                   = $pluginManager->getPlugIn($row->plugin, 'cron');
			$table                    = FabrikTable::getInstance(CronTable::class);
			$table->load($row->id);
			$plugin->setRow($table);
			$pluginParams       = $plugin->getParams();
			$thisListModel      = clone ($listModel);
			$thisAdminListModel = clone ($adminListModel);
			$tid                = (int) $rowParams->table;

			if ($tid !== 0)
			{
				$thisListModel->setId($tid);
				$this->log->message .= "\n\n$row->plugin\n listid = " . $thisListModel->getId();

				if ($plugin->requiresTableData())
				{
					$cron_row_limit = (int) $pluginParams->get('cron_row_limit', 100);
					$thisListModel->setLimits(0, $cron_row_limit);
					$thisListModel->getPagination(0, 0, $cron_row_limit);
					$data = $thisListModel->getData();
				}
			}
			else
			{
				$data = array();
			}

			$this->runningId = $row->id;
			// $$$ hugh - added table model param, in case plugin wants to do further table processing
			$c = $c + $plugin->process($data, $thisListModel, $thisAdminListModel);

			$this->log->message = $plugin->getLog() . "\n\n" . $this->log->message;

			if ($plugin->getParams()->get('log', 0) == 1)
			{
				$this->log->store();
			}

			// Email log message
			$recipient = $plugin->getParams()->get('log_email', '');

			if ($recipient != '')
			{
				$recipient = explode(',', $recipient);
				$subject   = $config->get('sitename') . ': ' . $row->plugin . ' scheduled task';
				$mailer->sendMail($config->get('mailfrom'), $config->get('fromname'), $recipient, $subject, $this->log->message, true);
			}

			if ($pluginParams->get('cron_reschedule_manual', '0') === '1')
			{
				$table->lastrun = Factory::getDate()->toSql();
				$table->store();
			}
		}

		$this->setRedirect('index.php?option=com_fabrik&view=crons', $c . ' records updated');
	}
}
