<?php
/**
 * List Copy Row plugin
 *
 * @package     Joomla.Plugin
 * @subpackage  Fabrik.list.copy
 * @copyright   Copyright (C) 2005-2016  Media A-Team, Inc. - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

// No direct access
defined('_JEXEC') or die('Restricted access');

use Fabrik\Component\Fabrik\Site\Plugin\AbstractListPlugin;
use Fabrik\Helpers\Worker;
use Joomla\CMS\Language\Text;

/**
 * Add an action button to the list to copy rows
 *
 * @package     Joomla.Plugin
 * @subpackage  Fabrik.list.copy
 * @since       3.0
 */
class PlgFabrik_ListCopy extends AbstractListPlugin
{
	/**
	 * Button prefix
	 *
	 * @var string
	 *
	 * @since 4.0
	 */
	protected $buttonPrefix = 'copy';

	/**
	 * Prep the button if needed
	 *
	 * @param   array  &$args Arguments
	 *
	 * @return  bool
	 *
	 * @since 4.0
	 */
	public function button(&$args)
	{
		parent::button($args);

		return true;
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
		return $this->getParams()->get('copytable_button_label', parent::buttonLabel());
	}

	/**
	 * Get button image
	 *
	 * @since   3.1b
	 *
	 * @return   string  image
	 */
	protected function getImageName()
	{
		$img = parent::getImageName();

		if ($img === 'copy.png')
		{
			$img = 'copy';
		}

		return $img;
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
		return 'copytable_access';
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
		return true;
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
		$model     = $this->getModel();
		$ids       = $this->app->input->get('ids', array(), 'array');
		$formModel = $model->getFormModel();

		return $model->copyRows($ids);
	}

	/**
	 * Get the message generated in process()
	 *
	 * @param   int $c plugin render order
	 *
	 * @return  string
	 *
	 * @since 4.0
	 */
	public function process_result($c)
	{
		$ids = $this->app->input->get('ids', array(), 'array');

		return Text::sprintf('PLG_LIST_ROWS_COPIED', count($ids));
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
		$opts             = $this->getElementJSOptions();
		$opts             = json_encode($opts);
		$this->jsInstance = "new FbListCopy($opts)";

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
		return 'FbListCopy';
	}
}
