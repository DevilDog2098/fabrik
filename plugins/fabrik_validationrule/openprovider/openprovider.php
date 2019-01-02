<?php
/**
 * Domain name look up against open provider service
 *
 * @package     Joomla.Plugin
 * @subpackage  Fabrik.validationrule.openprovider
 * @copyright   Copyright (C) 2005-2016  Media A-Team, Inc. - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

// No direct access
defined('_JEXEC') or die('Restricted access');

// Require the Open Provider API
require_once JPATH_SITE . '/plugins/fabrik_validationrule/openprovider/libs/api.php';

use Joomla\Component\Fabrik\Site\Plugin\AbstractValidationRulePlugin;

/**
 * Domain name look up against open provider service
 *
 * @package     Joomla.Plugin
 * @subpackage  Fabrik.validationrule.openprovider
 * @since       3.0
 */
class PlgFabrik_ValidationruleOpenprovider extends AbstractValidationRulePlugin
{
	/**
	 * Plugin name
	 *
	 * @var string
	 *
	 * @since 4.0
	 */
	protected $pluginName = 'openprovider';

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
	public function validate($data, $repeatCounter)
	{
		$params   = $this->getParams();
		$username = $params->get('openprovider_username');
		$password = $params->get('openprovider_password');
		$data     = strtolower($data);

		// Strip www. from front
		if (substr($data, 0, 4) == 'www.')
		{
			$data = substr($data, 4, strlen($data));
		}

		list($domain, $extension) = explode('.', $data, 2);
		$api     = new OP_API('https://api.openprovider.eu');
		$args    = array(
			'domains' => array(
				array(
					'name'      => $domain,
					'extension' => $extension
				)
			)
		);
		$request = new OP_Request;
		$request->setCommand('checkDomainRequest')
			->setAuth(array('username' => $username, 'password' => $password))
			->setArgs($args);

		$reply = $api->setDebug(0)->process($request);
		$res   = $reply->getValue();

		return $res[0]['status'] === 'active' ? false : true;
	}
}
