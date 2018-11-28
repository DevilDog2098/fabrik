<?php
/**
 * Content Type controller class.
 *
 * @package     Joomla.Administrator
 * @subpackage  Fabrik
 * @copyright   Copyright (C) 2005-2016  Media A-Team, Inc. - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 * @since       3.4.5
 */

namespace Joomla\Component\Fabrik\Administrator\Controller;

// No direct access
defined('_JEXEC') or die('Restricted access');

use Joomla\CMS\Factory;
use Joomla\Component\Fabrik\Administrator\Model\ContentTypeImportModel;
use Joomla\Component\Fabrik\Administrator\Model\FabModel;
use Joomla\Component\Fabrik\Administrator\Model\ListModel;
use Joomla\Component\Fabrik\Site\Model\FormModel;

/**
 * Content Type controller class.
 *
 * @package     Joomla.Administrator
 * @subpackage  Fabrik
 */
class ContentTypeController extends AbstractFormController
{
	/**
	 * Previews the content type's groups and elements
	 *
	 * @throws \Exception
	 *
	 * @since 4.0
	 */
	public function preview()
	{
		$contentType = $this->input->getString('contentType');
		$listModel = $this->getModel(ListModel::class);
		$model = $this->getModel(ContentTypeImportModel::class, '', array('listModel' => $listModel));
		$viewType = Factory::getDocument()->getType();
		$this->name = 'Fabrik';
		// @todo refactor to j4
		$this->setPath('view', COM_FABRIK_FRONTEND . '/views');
		$viewLayout = $this->input->get('layout', 'default');
		$view = $this->getView('Form', $viewType, '');
		$view->setLayout($viewLayout);

		/** @var FormModel  $formModel */
		$formModel = FabModel::getInstance(FormModel::class);
		$formModel->groups = $model->loadContentType($contentType)->preview();
		$view->setModel($formModel, true);

		$formModel->getGroupView('default');

		$view->preview();
		$res = new \stdClass;
		ob_start();
		$view->output();
		$res->preview = ob_get_contents();
		ob_end_clean();
		$res->aclMap = $model->aclCheckUI();
		echo json_encode($res);
		exit;
	}
}