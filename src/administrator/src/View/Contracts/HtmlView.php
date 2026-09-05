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

namespace XavierSpirlet\Component\Contractor\Administrator\View\Contracts;

\defined('_JEXEC') or die;

use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Toolbar\ToolbarHelper;
use Joomla\CMS\Factory;

class HtmlView extends BaseHtmlView
{
    protected $items;
    protected $pagination;
    protected $state;
    public $filterForm;
    public $activeFilters;

    public function display($tpl = null): void
    {
        // ACL security
        $app  = Factory::getApplication();
        $user = $app->getIdentity();
        if (!$user->authorise('core.manage.contracts', 'com_contractor')) {
            throw new \Exception(\Joomla\CMS\Language\Text::_('JERROR_ALERTNOAUTHOR'), 403);
        }

        $this->items         = $this->get('Items');
        $this->pagination    = $this->get('Pagination');
        $this->state         = $this->get('State');
        $this->filterForm    = $this->get('FilterForm');
        $this->activeFilters = $this->get('ActiveFilters');

        $this->addToolbar();
        parent::display($tpl);
    }

    protected function addToolbar(): void
    {
        ToolbarHelper::title(\Joomla\CMS\Language\Text::_('COM_CONTRACTOR_CONTRACT_LIST'), 'folder-open');
        
        ToolbarHelper::addNew('contract.add');
        ToolbarHelper::editList('contract.edit');
        ToolbarHelper::publish('contracts.publish', 'JTOOLBAR_PUBLISH', true);
        ToolbarHelper::unpublish('contracts.unpublish', 'JTOOLBAR_UNPUBLISH', true);
        ToolbarHelper::deleteList('JGLOBAL_CONFIRM_DELETE', 'contract.delete');
        ToolbarHelper::custom('contracts.email', 'mail', 'mail', 'COM_CONTRACTOR_BTN_SEND_MESSAGE', true);
    }
}