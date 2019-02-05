<?php
/**
 * Approval viz Model
 *
 * @package     Joomla.Plugin
 * @subpackage  Fabrik.visualization.approvals
 * @copyright   Copyright (C) 2005-2016  Media A-Team, Inc. - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

namespace Fabrik\Plugin\FabrikVisualization\Approvals\Model;

// No direct access
defined('_JEXEC') or die('Restricted access');

use Fabrik\Component\Fabrik\Administrator\Model\FabrikModel;
use Fabrik\Component\Fabrik\Site\Model\AbstractVisualizationModel;
use Fabrik\Component\Fabrik\Site\Model\ListModel;
use Fabrik\Helpers\Html;
use Fabrik\Helpers\StringHelper as FStringHelper;

/**
 * Approval viz Model
 *
 * @package     Joomla.Plugin
 * @subpackage  Fabrik.visualization.approvals
 * @since       4.0
 */
class ApprovalsModel extends AbstractVisualizationModel
{
	/**
	 * Get the rows of data to show in the viz
	 *
	 * @return   array
	 *
	 * @since 4.0
	 */
	public function getRows()
	{
		$params     = $this->getParams();
		$ids        = (array) $params->get('approvals_table');
		$approveEls = (array) $params->get('approvals_approve_element');
		$titles     = (array) $params->get('approvals_title_element');
		$users      = (array) $params->get('approvals_user_element');
		$contents   = (array) $params->get('approvals_content_element');

		$this->rows = array();

		for ($x = 0; $x < count($ids); $x++)
		{
			$asfields  = array();
			$fields    = array();
			$listModel = FabrikModel::getInstance(ListModel::class);
			$listModel->setId($ids[$x]);
			$item      = $listModel->getTable();
			$formModel = $listModel->getFormModel();
			$formModel->getForm();
			$db    = $listModel->getDb();
			$query = $db->getQuery(true);

			$this->asField($formModel, $approveEls[$x], $asfields, array('alias' => 'approve'));
			$this->asField($formModel, $titles[$x], $asfields, array('alias' => 'title'));
			$this->asField($formModel, $users[$x], $asfields, array('alias' => 'user'));
			$this->asField($formModel, $contents[$x], $asfields, array('alias' => 'content'));

			$query->select($db->quote($item->label) . " AS type, " . $item->db_primary_key . ' AS pk, ' . implode(',', $asfields))
				->from($db->quoteName($item->db_table_name));
			$query = $listModel->buildQueryJoin($query);
			$query->where(str_replace('___', '.', $approveEls[$x]) . ' = 0');
			$db->setQuery($query, 0, 5);
			$rows = $db->loadObjectList();

			foreach ($rows as &$row)
			{
				$row->view   = 'index.php?option=com_' . $this->package . '&task=form.view&formid=' . $formModel->getId() . '&rowid=' . $row->pk;
				$row->rowid  = $row->pk;
				$row->listid = $ids[$x];
			}

			$this->rows = array_merge($this->rows, $rows);
		}

		return $this->rows;
	}

	/**
	 * Load up a field 'select as' statement
	 *
	 * @param   JModel   $formModel Form model
	 * @param   string   $fieldName Element full name
	 * @param   array   &$asfields  As fields to append as statement to
	 * @param   array    $opts      Options
	 *
	 * @throws \RuntimeException
	 *
	 * @return  void
	 *
	 * @since 4.0
	 */
	private function asField($formModel, $fieldName, &$asfields, $opts)
	{
		$elementModel = $formModel->getElement($fieldName);
		$fields       = array();

		if ($elementModel)
		{
			if ($elementModel->getElement()->published <> 1)
			{
				throw new \RuntimeException('Approval ' . $fieldName . ' element must be published', 500);
			}

			$elementModel->getAsField_html($asfields, $fields, $opts);
		}
	}

	/**
	 * Disapprove a record
	 *
	 * @return  void
	 *
	 * @since 4.0
	 */
	public function disapprove()
	{
		$this->decide(0);

		echo Html::icon('icon-remove');
	}

	/**
	 * Approve a record
	 *
	 * @return  void
	 *
	 * @since 4.0
	 */
	public function approve()
	{
		$this->decide(1);

		echo Html::icon('icon-ok');
	}

	/**
	 * Decide if we should approve or not?
	 *
	 * @param   string $v update value
	 *
	 * @return  void
	 *
	 * @since 4.0
	 */
	protected function decide($v)
	{
		$input      = $this->app->input;
		$params     = $this->getParams();
		$ids        = (array) $params->get('approvals_table');
		$approveEls = (array) $params->get('approvals_approve_element');

		foreach ($ids as $key => $listId)
		{
			if ($listId == $input->getInt('listid'))
			{
				$listModel = FabrikModel::getInstance(ListModel::class);
				$listModel->setId($input->getInt('listid'));
				$item  = $listModel->getTable();
				$db    = $listModel->getDbo();
				$query = $db->getQuery(true);
				$el    = FStringHelper::safeColName($approveEls[$key]);
				$query->update($db->quoteName($item->db_table_name))->set($el . ' = ' . $db->quote($v))
					->where($item->db_primary_key . ' = ' . $db->quote($input->get('rowid')));
				$db->setQuery($query);
				$db->execute();
			}
		}
	}

	/**
	 * Set an array of list id's whose data is used inside the visualization
	 *
	 * @return  void
	 *
	 * @since 4.0
	 */
	protected function setListIds()
	{
		if (!isset($this->listids))
		{
			$params        = $this->getParams();
			$this->listids = (array) $params->get('approvals_table');
		}
	}
}
