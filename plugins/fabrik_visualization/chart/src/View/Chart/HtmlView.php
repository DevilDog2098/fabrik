<?php
/**
 * Fabrik Google Chart HTML View
 *
 * @package     Joomla.Plugin
 * @subpackage  Fabrik.visualization.chart
 * @copyright   Copyright (C) 2005-2016  Media A-Team, Inc. - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

namespace Fabrik\Plugin\FabrikVisualization\Chart\View\Chart;

// No direct access
defined('_JEXEC') or die('Restricted access');

use Fabrik\Helpers\Html;
use Fabrik\Helpers\Worker;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\HtmlView as BaseView;

/**
 * Fabrik Google Chart HTML View
 *
 * @package     Joomla.Plugin
 * @subpackage  Fabrik.visualization.chart
 * @since       4.0
 */
class HtmlView extends BaseView
{
	/**
	 * Execute and display a template script.
	 *
	 * @param   string $tpl The name of the template file to parse; automatically searches through the template paths.
	 *
	 * @return  mixed  A string if successful, otherwise a JError object.
	 *
	 * @since 4.0
	 */
	public function display($tpl = null)
	{
		$app                    = Factory::getApplication();
		$input                  = $app->input;
		$srcs                   = Html::framework();
		$srcs['FbListFilter']   = 'media/com_fabrik/js/listfilter.js';
		$srcs['AdvancedSearch'] = 'media/com_fabrik/js/advanced-search.js';
		$model                  = $this->getModel();
		$usersConfig            = ComponentHelper::getParams('com_fabrik');
		$model->setId($input->getInt('id', $usersConfig->get('visualizationid', $input->getInt('visualizationid', 0))));
		$this->row = $model->getVisualization();

		if (!$model->canView())
		{
			echo Text::_('JERROR_ALERTNOAUTHOR');

			return false;
		}

		if ($this->row->published == 0)
		{
			throw new \RuntimeException(Text::_('JERROR_ALERTNOAUTHOR'));
		}

		$this->requiredFiltersFound = $this->get('RequiredFiltersFound');

		if ($this->requiredFiltersFound)
		{
			$this->chart = $this->get('Chart');
		}
		else
		{
			$this->chart = '';
		}

		$params              = $model->getParams();
		$this->params        = $params;
		$viewName            = $this->getName();
		$pluginManager       = Worker::getPluginManager();
		$plugin              = $pluginManager->getPlugIn('chart', 'visualization');
		$this->containerId   = $this->get('ContainerId');
		$this->filters       = $this->get('Filters');
		$this->showFilters   = $model->showFilters();
		$this->filterFormURL = $this->get('FilterFormURL');

		$tpl      = $params->get('chart_layout', $tpl);
		$tmplpath = JPATH_ROOT . '/plugins/fabrik_visualization/chart/tmpl/' . $tpl;
		$this->_setPath('template', $tmplpath);
		Html::stylesheetFromPath('plugins/fabrik_visualization/chart/tmpl/' . $tpl . '/template.css');

		// Assign something to Fabrik.blocks to ensure we can clear filters
		$ref = $model->getJSRenderContext();
		$js  = "$ref = {};";
		$js  .= "\n" . "Fabrik.addBlock('$ref', $ref);";
		$js  .= $model->getFilterJs();

		Html::iniRequireJs($model->getShim());
		Html::script($srcs, $js);
		echo parent::display();
	}
}
