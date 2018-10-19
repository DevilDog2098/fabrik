<?php
/**
 * Admin List Edit Tmpl
 *
 * @package     Joomla.Administrator
 * @subpackage  Fabrik
 * @copyright   Copyright (C) 2005-2016  Media A-Team, Inc. - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 * @since       3.0
 */

// No direct access
defined('_JEXEC') or die('Restricted access');

JHtml::addIncludePath(JPATH_COMPONENT . '/helpers/html');
JHTML::stylesheet('administrator/components/com_fabrik/views/fabrikadmin.css');
JHtml::_('behavior.tooltip');
//FabrikHelperHTML::formvalidation();
JHtml::_('behavior.formvalidator');
JHtml::_('behavior.keepalive');

?>
<script type="text/javascript">

    Joomla.submitbutton = function(task) {
        requirejs(['fab/fabrik'], function (Fabrik) {
            if (task !== 'list.cancel' && !Fabrik.controller.canSaveForm()) {
                window.alert('Please wait - still loading');
                return false;
            }
            if (task == 'list.cancel' || document.formvalidator.isValid(document.id('adminForm'))) {
				<?php echo $this->form->getField('introduction')->save(); ?>
                window.fireEvent('form.save');
                Joomla.submitform(task, document.getElementById('adminForm'));
            } else {
                window.alert('<?php echo $this->escape(FText::_('JGLOBAL_VALIDATION_FORM_FAILED'));?>');
            }
        });
    }
</script>

<form action="<?php JRoute::_('index.php?option=com_fabrik'); ?>" method="post" name="adminForm" id="adminForm" class="form-validate">
    <div class="row" id="elementFormTable">

        <div class="col-md-2">
            <ul class="nav flex-column"style="margin-top:40px">
                <li class="nav-item">
                    <a class="nav-link active" data-toggle="tab" href="#detailsX">
						<?php echo FText::_('COM_FABRIK_DETAILS')?>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" data-toggle="tab" href="#data">
						<?php echo FText::_('COM_FABRIK_DATA')?>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" data-toggle="tab" href="#publishing">
						<?php echo FText::_('COM_FABRIK_GROUP_LABEL_PUBLISHING_DETAILS')?>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" data-toggle="tab" href="#access">
						<?php echo FText::_('COM_FABRIK_GROUP_LABEL_RULES_DETAILS')?>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" data-toggle="tab" href="#tabplugins">
						<?php echo FText::_('COM_FABRIK_GROUP_LABEL_PLUGINS_DETAILS')?>
                    </a>
                </li>
            </ul>
        </div>
        <div class="col-md-10">
            <div class="tab-content">
                <div class="tab-pane active" id="detailsX">
				    <?php echo $this->loadTemplate('details'); ?>
                </div>
                <div class="tab-pane" id="data">
		            <?php echo $this->loadTemplate('data'); ?>
                </div>
                <div class="tab-pane" id="publishing">
		            <?php echo $this->loadTemplate('publishing'); ?>
                </div>
                <div class="tab-pane" id="access">
		            <?php echo $this->loadTemplate('access'); ?>
                </div>
                <div class="tab-pane" id="tabplugins">
		            <?php echo $this->loadTemplate('plugins'); ?>
                </div>
            </div>

            <input type="hidden" name="task" value="" />
			<?php echo JHtml::_('form.token'); ?>
        </div>
    </div>
</form>
