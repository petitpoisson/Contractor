<?php
/**
 * @package     Joomla.Administrator
 * @subpackage  com_contractor
 *
 * @author      Xavier Spirlet (Petitpoisson) <https://www.petitpoisson.be>
 * @copyright   Copyright (C) 2026 Xavier Spirlet. All rights reserved.
 * @license     GNU General Public License version 3 or later; see LICENSE.txt
 * @link        https://www.petitpoisson.be
 */

namespace XavierSpirlet\Component\Contractor\Administrator\View\Logs;

\defined('_JEXEC') or die;

use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Toolbar\ToolbarHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Factory;

class HtmlView extends BaseHtmlView
{
    public $items;
    public $pagination;
    public $state;
    public $filterForm;
    public $activeFilters;

    public function display($tpl = null)
    {
        // ACL security
        $app  = Factory::getApplication();
        $user = $app->getIdentity();
        if (!$user->authorise('core.manage.logs', 'com_contractor')) {
            throw new \Exception(\Joomla\CMS\Language\Text::_('JERROR_ALERTNOAUTHOR'), 403);
        }

        $this->items         = $this->get('Items');
        $this->pagination    = $this->get('Pagination');
        $this->state         = $this->get('State');
        $this->filterForm    = $this->get('FilterForm');
        $this->activeFilters = $this->get('ActiveFilters');

        $this->addToolbar();

        return parent::display($tpl);
    }

    protected function addToolbar()
    {
        ToolbarHelper::title(\Joomla\CMS\Language\Text::_('COM_CONTRACTOR_SYSLOGS'), 'list');
        ToolbarHelper::custom('logs.export', 'download', 'download', Text::_('COM_CONTRACTOR_EXPORTLOGS'), false);
        ToolbarHelper::custom('logs.prune', 'trash', 'trash', 'COM_CONTRACTOR_LOG_PRUNE', false);
    }
}