<?php
/**
 * Cron controller class.
 *
 * @package     Joomla.Administrator
 * @subpackage  Fabrik
 * @copyright   Copyright (C) 2005-2016  Media A-Team, Inc. - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 * @since       1.6
 */

namespace Fabrik\Component\Fabrik\Administrator\Controller;

// No direct access
defined('_JEXEC') or die('Restricted access');

/**
 * Cron controller class.
 *
 * @package     Joomla.Administrator
 * @subpackage  Fabrik
 * @since       4.0
 */
class CronController extends AbstractFormController
{
	/**
	 * The prefix to use with controller messages.
	 *
	 * @var	string
	 *
	 * @since 4.0
	 */
	protected $text_prefix = 'COM_FABRIK_CRON';
}
