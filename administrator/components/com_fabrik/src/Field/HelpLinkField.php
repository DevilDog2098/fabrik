<?php
/**
 * Renders a Fabrik Help link
 *
 * @package     Joomla
 * @subpackage  Fabrik
 * @copyright   Copyright (C) 2005-2016  Media A-Team, Inc. - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 * @since       3.0.9
 */

namespace Fabrik\Component\Fabrik\Administrator\Field;

use Joomla\CMS\Form\FormField;
use Joomla\CMS\Language\Text;

// No direct access
defined('_JEXEC') or die('Restricted access');

/**
 * Renders a Fabrik Help link
 *
 * @package  Fabrik
 * @since    4.0
 */
class HelpLinkField extends FormField
{
	use FormFieldNameTrait;

	/**
	 * @var string
	 *
	 * @since 4.0
	 */
	protected $type = 'helplink';

	/**
	 * Return blank label
	 *
	 * @return  string  The field label markup.
	 *
	 * @since 4.0
	 */

	protected function getLabel()
	{
		return '';
	}

	/**
	 * Get the input - a right floated help icon
	 *
	 * @return string
	 *
	 * @since 4.0
	 */
	public function getInput()
	{
		$url   = $this->element['url'] ? (string) $this->element['url'] : '';
		$js    = 'Joomla.popupWindow(\'' . Text::_($url) . '\', \'Help\', 800, 600, 1);return false';
		$label = '<div style="float:right;">';
		$label .= '<a class="btn btn-small btn-info" href="#" rel="help" onclick="' . $js . '">';
		$label .= '<i class="icon-help icon-32-help icon-question-sign"></i> ' . Text::_('JHELP') . '</a></div>';

		return $label;
	}
}
