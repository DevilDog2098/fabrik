<?php
/**
 * PHP Validation Rule
 *
 * @package     Joomla.Plugin
 * @subpackage  Fabrik.validationrule.php
 * @copyright   Copyright (C) 2005-2016  Media A-Team, Inc. - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

// No direct access
defined('_JEXEC') or die('Restricted access');

use Joomla\CMS\Plugin\PluginHelper;
use Fabrik\Component\Fabrik\Site\Plugin\AbstractValidationRulePlugin;
use Joomla\Registry\Registry;
use Fabrik\Helpers\Worker;

/**
 * PHP Validation Rule
 *
 * @package     Joomla.Plugin
 * @subpackage  Fabrik.validationrule.php
 * @since       3.0
 */
class PlgFabrik_ValidationrulePhp extends AbstractValidationRulePlugin
{
	/**
	 * Plugin name
	 *
	 * @var string
	 *
	 * @since 4.0
	 */
	protected $pluginName = 'php';

	/**
	 * Validate the elements data against the rule
	 *
	 * @param   string $data          To check
	 * @param   int    $repeatCounter Repeat group counter
	 *
	 * @return  bool  true if validation passes, false if fails
	 *
	 * @since 4.0
	 */
	public function validate($data, $repeatCounter = 0)
	{
		// For multi-select elements
		if (is_array($data))
		{
			$data = implode('', $data);
		}

		$params  = $this->getParams();
		$doMatch = $params->get('php-match');

		if ($doMatch)
		{
			return $this->_eval($data, $repeatCounter);
		}

		return true;
	}

	/**
	 * Checks if the validation should replace the submitted element data
	 * if so then the replaced data is returned otherwise original data returned
	 *
	 * @param   string $data          Original data
	 * @param   int    $repeatCounter Repeat group counter
	 *
	 * @return  string    original or replaced data
	 *
	 * @since 4.0
	 */
	public function replace($data, $repeatCounter = 0)
	{
		$params  = $this->getParams();
		$doMatch = $params->get('php-match');

		if (!$doMatch)
		{
			return $this->_eval($data, $repeatCounter);
		}

		return $data;
	}

	/**
	 * Run eval
	 *
	 * @param   string $data          Original data
	 * @param   int    $repeatCounter Repeat group counter
	 *
	 * @return  string    Evaluated PHP function
	 *
	 * @since 4.0
	 */
	private function _eval($data, $repeatCounter = 0)
	{
		$params        = $this->getParams();
		$elementPlugin = $this->elementPlugin;
		$formModel     = $elementPlugin->getFormModel();
		$formData      = $formModel->formData;
		$w             = new Worker;
		$phpCode       = $params->get('php-code');
		$phpCode       = $w->parseMessageForPlaceHolder($phpCode, $formData, true, true);
		/**
		 * $$$ hugh - added trigger_error(""), which will "clear" any existing errors,
		 * otherwise logEval will pick up and report notices and warnings generated
		 * by the rest of our code, which can be VERY confusing.  Note that this required a tweak
		 * to logEval, as error_get_last won't be null after doing this, but $error['message'] will
		 * be empty.
		 * $$$ hugh - moved the $trigger_error() into a helper func
		 */
		Worker::clearEval();
		$return = @eval($phpCode);
		Worker::logEval($return, 'Caught exception on php validation of ' . $elementPlugin->getFullName(true, false) . ': %s');

		return $return;
	}

	/**
	 * Get the base icon image as defined by the J Plugin options
	 *
	 * @since   3.1b2
	 *
	 * @return  string
	 */
	public function iconImage()
	{
		$plugin       = PluginHelper::getPlugin('fabrik_validationrule', $this->pluginName);
		$globalParams = new Registry($plugin->params);
		$default      = $globalParams->get('icon', 'star');
		$params       = $this->getParams();

		return $params->get('icon', $default);
	}
}
