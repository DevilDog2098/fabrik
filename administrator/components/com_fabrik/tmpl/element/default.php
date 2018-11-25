<?php
/**
 * Admin Element Edit Tmpl
 *
 * @package     Joomla.Administrator
 * @subpackage  Fabrik
 * @copyright   Copyright (C) 2005-2016  Media A-Team, Inc. - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 * @since       3.0
 */

// No direct access
defined('_JEXEC') or die('Restricted access');

use Joomla\CMS\Router\Route;
use Fabrik\Helpers\Html;
use Joomla\CMS\Language\Text;
use Joomla\CMS\HTML\HTMLHelper;

HTMLHelper::addIncludePath(JPATH_COMPONENT . '/helpers/html');
HTMLHelper::stylesheet('administrator/components/com_fabrik/tmpl/fabrikadmin.css');
HTMLHelper::_('behavior.tooltip');
Html::formvalidation();
HTMLHelper::_('behavior.keepalive');

Text::script('COM_FABRIK_SUBOPTS_VALUES_ERROR');
?>

<script type="text/javascript">

	Joomla.submitbutton = function(task) {
		requirejs(['fab/fabrik'], function (Fabrik) {
			if (task !== 'element.cancel' && !Fabrik.controller.canSaveForm()) {
				window.alert('Please wait - still loading');
				return false;
			}
			if (task == 'element.cancel' || document.formvalidator.isValid(document.id('adminForm'))) {
				window.fireEvent('form.save');
				Joomla.submitform(task, document.getElementById('adminForm'));
			} else {
				window.alert('<?php echo $this->escape(Text::_('JGLOBAL_VALIDATION_FORM_FAILED'));?>');
			}
		});
	}
</script>
<form action="<?php Route::_('index.php?option=com_fabrik'); ?>" method="post" name="adminForm" id="adminForm" class="form-validate">

<?php if ($this->item->parent_id != 0)
{
	?>
	<div id="system-message" class="alert alert-notice">
		<strong><?php echo Text::_('COM_FABRIK_ELEMENT_PROPERTIES_LINKED_TO') ?>: <?php echo $this->parent->label ?></strong>

		<p><a href="#" id="swapToParent" class="element_<?php echo $this->parent->id ?>"><span class="icon-pencil"></span>
		<?php echo Text::_('COM_FABRIK_EDIT') . ' ' . $this->parent->label ?></a></p>

		<label><?php echo Text::_('COM_FABRIK_OR')?> <span class="icon-magnet"></span>
		<input id="unlink" name="unlink" id="unlinkFromParent" type="checkbox"> <?php echo Text::_('COM_FABRIK_UNLINK') ?>
		</label>
	</div>
<?php
}?>
	<div class="row" id="elementFormTable">

		<div class="col-2">

			<ul class="nav flex-column">
				<li class="nav-item">
			    	<a class="nav-link active" data-toggle="tab" href="#tab-details">
			    		<?php echo Text::_('COM_FABRIK_DETAILS')?>
			    	</a>
			    </li>
			    <li class="nav-item">
			    	<a class="nav-link" data-toggle="tab" href="#tab-publishing">
			    		<?php echo Text::_('COM_FABRIK_PUBLISHING')?>
			    	</a>
			    </li>
			    <li class="nav-item">
			    	<a class="nav-link" data-toggle="tab" href="#tab-access">
			    		<?php echo Text::_('COM_FABRIK_GROUP_LABEL_RULES_DETAILS')?>
			    	</a>
			    </li>
			    <li class="nav-item">
			    	<a class="nav-link" data-toggle="tab" href="#tab-listview">
			    		<?php echo Text::_('COM_FABRIK_LIST_VIEW_SETTINGS')?>
			    	</a>
			    </li>
			    <li class="nav-item">
			    	<a class="nav-link" data-toggle="tab" href="#tab-validations">
			    		<?php echo Text::_('COM_FABRIK_VALIDATIONS')?>
			    	</a>
			    </li>
			    <li class="nav-item">
			    	<a class="nav-link" data-toggle="tab" href="#tab-javascript">
			    		<?php echo Text::_('COM_FABRIK_JAVASCRIPT')?>
			    	</a>
			    </li>
			</ul>
		</div>

		<div class="col-10">
            <div class="tab-content">
                <div class="tab-pane active" id="tab-details">
                    <?php echo $this->loadTemplate('details'); ?>
                </div>
                <div class="tab-pane" id="tab-publishing">
                    <?php echo $this->loadTemplate('publishing'); ?>
                </div>
                <div class="tab-pane" id="tab-access">
                    <?php echo $this->loadTemplate('access'); ?>
                </div>
                <div class="tab-pane" id="tab-listview">
                    <?php echo $this->loadTemplate('listview'); ?>
                </div>
                <div class="tab-pane" id="tab-validations">
                    <?php echo $this->loadTemplate('validations'); ?>
                </div>
                <div class="tab-pane" id="tab-javascript">
                    <?php echo $this->loadTemplate('javascript'); ?>
                </div>
            </div>
		</div>
	</div>

	<input type="hidden" name="task" value="" />
	<input type="hidden" name="redirectto" value="" />
	<?php echo HTMLHelper::_('form.token'); ?>
</form>
