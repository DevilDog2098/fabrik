<?php
/**
 * Admin Form Edit Tmpl
 *
 * @package     Joomla.Administrator
 * @subpackage  Fabrik
 * @copyright   Copyright (C) 2005-2016  Media A-Team, Inc. - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 * @since       3.0
 */

// No direct access
defined('_JEXEC') or die('Restricted access');

use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Language\Text;
use Fabrik\Helpers\Html;

HTMLHelper::addIncludePath(JPATH_COMPONENT . '/helpers/html');
HTMLHelper::stylesheet('administrator/components/com_fabrik/tmpl/fabrikadmin.css');
HTMLHelper::_('behavior.tooltip');
Html::formvalidation();
HTMLHelper::_('behavior.keepalive');
?>

<script type="text/javascript">

	Joomla.submitbutton = function(task) {

		requirejs(['fab/fabrik'], function (Fabrik) {
			var currentGroups = document.id('jform_current_groups');
			var createNew = document.id('jform__createGroup1').checked;

			if (typeOf(currentGroups) !== 'null') {
				Object.each(currentGroups.options, function (opt) {
					opt.selected = true;
				});
			}

			if (task !== 'form.cancel') {
				if (!Fabrik.controller.canSaveForm()) {
					window.alert('<?php echo Text::_('COM_FABRIK_ERR_ONE_GROUP_MUST_BE_SELECTED'); ?>');
					return false;
				}

				if (typeOf(currentGroups) !== 'null' && currentGroups.options.length === 0 && createNew === false) {
					window.alert('Please select at least one group');
					return false;
				}
			}
			if (task == 'form.cancel' || document.formvalidator.isValid(document.id('adminForm'))) {
				window.fireEvent('form.save');
				Joomla.submitform(task, document.getElementById('adminForm'));
			} else {
				alert('<?php echo $this->escape(Text::_('JGLOBAL_VALIDATION_FORM_FAILED'));?>');
			}
		});
	}
</script>

<form action="<?php Route::_('index.php?option=com_fabrik'); ?>" method="post" name="adminForm" id="adminForm" class="form-validate">
	<div class="row-fluid">
		<ul class="nav nav-tabs">
			<li class="nav-item active">
		    	<a class="nav-link" data-toggle="tab" href="#tab-details">
		    		<?php echo Text::_('COM_FABRIK_DETAILS'); ?>
		    	</a>
		    </li>
		    <li class="nav-item">
		    	<a class="nav-link" data-toggle="tab" href="#tab-buttons">
		    		<?php echo Text::_('COM_FABRIK_BUTTONS'); ?>
		    	</a>
		    </li>
		    <li class="nav-item">
		    	<a class="nav-link" data-toggle="tab" href="#tab-process">
		    		<?php echo Text::_('COM_FABRIK_FORM_PROCESSING'); ?>
		    	</a>
		    </li>
		    <li class="nav-item">
		    	<a class="nav-link" data-toggle="tab" href="#tab-publishing">
		    		<?php echo Text::_('COM_FABRIK_GROUP_LABEL_PUBLISHING_DETAILS')?>
		    	</a>
		    </li>
		    <li class="nav-item">
		    	<a class="nav-link" data-toggle="tab" href="#tab-groups">
		    		<?php echo Text::_('COM_FABRIK_GROUPS')?>
		    	</a>
		    </li>
		    <li class="nav-item">
		    	<a class="nav-link" data-toggle="tab" href="#tab-layout">
		    		<?php echo Text::_('COM_FABRIK_LAYOUT')?>
		    	</a>
		    </li>
		    <li class="nav-item">
		    	<a class="nav-link" data-toggle="tab" href="#tab-options">
		    		<?php echo Text::_('COM_FABRIK_OPTIONS')?>
		    	</a>
		    </li>
		    <li class="nav-item">
		    	<a class="nav-link" data-toggle="tab" href="#tab-plugins">
		    		<?php echo Text::_('COM_FABRIK_PLUGINS')?>
		    	</a>
		    </li>
		</ul>
	</div>
	<div class="tab-content">
		<?php
		echo $this->loadTemplate('details');
		echo $this->loadTemplate('buttons');
		echo $this->loadTemplate('process');
		echo $this->loadTemplate('publishing');
		echo $this->loadTemplate('groups');
		echo $this->loadTemplate('templates');
		echo $this->loadTemplate('options');
		echo $this->loadTemplate('plugins');
		?>
	</div>
	<input type="hidden" name="task" value="" />
	<?php echo HTMLHelper::_('form.token'); ?>
</form>
