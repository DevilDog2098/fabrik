<?php
/**
 * Add an action button to run web service
 *
 * @package     Joomla.Plugin
 * @subpackage  Fabrik.list.webservice
 * @copyright   Copyright (C) 2005-2016  Media A-Team, Inc. - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

// No direct access
defined('_JEXEC') or die('Restricted access');

use Fabrik\Component\Fabrik\Site\Plugin\AbstractListPlugin;
use Fabrik\Component\Fabrik\Site\WebService\AbstractWebService;
use Fabrik\Helpers\Html;
use Fabrik\Helpers\ArrayHelper as FArrayHelper;
use Joomla\CMS\Language\Text;

/**
 * Add an action button to run web service
 *
 * @package     Joomla.Plugin
 * @subpackage  Fabrik.list.webservice
 * @since       3.0
 */
class PlgFabrik_ListWebservice extends AbstractListPlugin
{
	/**
	 * Button prefix
	 *
	 * @var string
	 *
	 * @since 4.0
	 */
	protected $buttonPrefix = 'webservice';

	/**
	 * Does the plugin render a button at the top of the list?
	 *
	 * @return    bool
	 *
	 * @since 4.0
	 */
	public function topButton()
	{
		return true;
	}

	/**
	 * Create the HTML for rendering a button in the top button list
	 *
	 * @return    string    <a> link
	 *
	 * @since 4.0
	 */
	public function topButton_result()
	{
		if ($this->canUse())
		{
			$name      = $this->_getButtonName();
			$label     = $this->buttonLabel();
			$tmpl      = $this->getModel()->getTmpl();
			$imageName = $this->getParams()->get('list_' . $this->buttonPrefix . '_image_name', 'arrow-up.png');

			$img = Html::image($imageName, 'list', $tmpl, array('alt' => $label));

			return '<a data-list="' . $this->context . '" href="#" class="' . $name . ' listplugin" title="' . $label . '">'
				. $img . '<span>' . $label . '</span></a>';
		}
	}

	/**
	 * Prep the button. Show it if in heading (top of list) but not in rows
	 *
	 * @param   array  &$args Arguments
	 *
	 * @return  bool;
	 *
	 * @since 4.0
	 */
	public function button(&$args)
	{
		$opts               = FArrayHelper::getValue($args, 0, array());
		$model              = $this->getModel();
		$this->buttonAction = $model->actionMethod();
		$this->context      = $model->getRenderContext();
		$heading            = (bool) FArrayHelper::getValue($opts, 'heading', false);

		return $heading;
	}

	/**
	 * Get the button label
	 *
	 * @return  string
	 *
	 * @since 4.0
	 */
	protected function buttonLabel()
	{
		return $this->getParams()->get('webservice_button_label', parent::buttonLabel());
	}

	/**
	 * Get the parameter name that defines the plugins acl access
	 *
	 * @return  string
	 *
	 * @since 4.0
	 */
	protected function getAclParam()
	{
		return 'webservice_access';
	}

	/**
	 * Can the plug-in select list rows
	 *
	 * @return  bool
	 *
	 * @since 4.0
	 */
	public function canSelectRows()
	{
		return false;
	}

	/**
	 * Do the plug-in action
	 *
	 * @param   array $opts Custom options
	 *
	 * @return  bool
	 *
	 * @since 4.0
	 */
	public function process($opts = array())
	{
		$params            = $this->getParams();
		$fk                = $params->get('webservice_foreign_key');
		$model             = $this->getModel();
		$formModel         = $model->getFormModel();
		$foriegnKeyElement = $formModel->getElement($fk, true);

		if (!$foriegnKeyElement)
		{
			throw new \UnexpectedValueException('Webservice list plugin requires a foriegn key element to be selected');
		}

		$fk          = $foriegnKeyElement->getElement()->name;
		$credentials = $this->getCredentials();

		$driver  = $params->get('webservice_driver');
		$opts    = array('driver' => $driver, 'endpoint' => $params->get('webservice_url'), 'credentials' => $credentials);
		$service = AbstractWebService::getInstance($opts);
		$filters = $this->getServiceFilters($service);
		$service->setMap($this->getMap($formModel));
		$filters     = array_merge($opts['credentials'], $filters);
		$method      = $params->get('webservice_get_method');
		$startPoint  = $params->get('webservice_start_point', '');
		$serviceData = $service->get($method, $filters, $startPoint, null);
		$update      = (bool) $params->get('webservice_update_existing', false);
		$service->storeLocally($model, $serviceData, $fk, $update);
		$this->msg = Text::sprintf($params->get('webservice_msg'), $service->addedCount, $service->updateCount);

		return true;
	}

	/**
	 * Get the data map to transform web service data into list data
	 *
	 * @param   object $formModel Form model
	 *
	 * @return  array  data map
	 *
	 * @since 4.0
	 */
	protected function getMap($formModel)
	{
		$params = $this->getParams();
		$map    = json_decode($params->get('webservice_map'));
		$return = array();
		$from   = $map->map_from;
		$to     = $map->map_to;
		$match  = $map->map_match;
		$value  = $map->map_value;
		$eval   = $map->map_eval;
		$n      = count($from);

		for ($i = 0; $i < $n; $i++)
		{
			$tid      = $formModel->getElement($to[$i], true)->getElement()->name;
			$return[] = array('from' => $from[$i], 'to' => $tid, 'value' => $value[$i], 'match' => $match[$i], 'eval' => (bool) $eval[$i]);
		}

		return $return;
	}

	/**
	 * Get an array of key/value filters to send to the web service
	 *
	 * @param   AbstractWebService $service The current web service being used
	 *
	 * @return  array  key/val pairs
	 *
	 * @since 4.0
	 */
	protected function getServiceFilters(AbstractWebService $service)
	{
		$params  = $this->getParams();
		$filters = json_decode($params->get('webservice_filters'));
		$return  = array();
		$keys    = $filters->webservice_filters_key;
		$vals    = $filters->webservice_filters_value;
		$types   = $filters->webservice_filters_type;
		$n       = count($keys);

		for ($i = 0; $i < $n; $i++)
		{
			$return[$keys[$i]] = $service->getFilterValue($vals[$i], $types[$i]);
		}

		return $return;
	}

	/**
	 * Get sign in credentials to the service
	 *
	 * @return  array  Login credentials
	 *
	 * @since 4.0
	 */
	protected function getCredentials()
	{
		$params      = $this->getParams();
		$credentials = json_decode($params->get('webservice_credentials'));
		$return      = array();
		$keys        = isset($credentials->webservice_credentials_key) ? $credentials->webservice_credentials_key : array();
		$vals        = isset($credentials->webservice_credentials_value) ? $credentials->webservice_credentials_value : array();
		$n           = count($keys);

		for ($i = 0; $i < $n; $i++)
		{
			$return[$keys[$i]] = $vals[$i];
		}

		return $return;
	}

	/**
	 * Get the message generated in process()
	 *
	 * @param   int $c Plugin render order
	 *
	 * @return  string
	 *
	 * @since 4.0
	 */
	public function process_result($c)
	{
		return $this->msg;
	}

	/**
	 * Return the javascript to create an instance of the class defined in formJavascriptClass
	 *
	 * @param   array $args Array [0] => string table's form id to contain plugin
	 *
	 * @return bool
	 *
	 * @since 4.0
	 */
	public function onLoadJavascriptInstance($args)
	{
		parent::onLoadJavascriptInstance($args);
		$opts                 = $this->getElementJSOptions();
		$opts->requireChecked = false;
		$opts                 = json_encode($opts);
		$this->jsInstance     = "new FbListWebservice($opts)";

		return true;
	}

	/**
	 * Load the AMD module class name
	 *
	 * @return string
	 *
	 * @since 4.0
	 */
	public function loadJavascriptClassName_result()
	{
		return 'FbListWebservice';
	}
}
