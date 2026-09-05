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

namespace XavierSpirlet\Component\Contractor\Administrator\View\Contract;

\defined('_JEXEC') or die;

use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Toolbar\ToolbarHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Factory;

class HtmlView extends BaseHtmlView
{
    protected $form;
    protected $item;

    public function display($tpl = null): void
    {
        $this->form = $this->get('Form');
        $this->item = $this->get('Item');

        // ACL security
        $app  = Factory::getApplication();
        $user = $app->getIdentity();
        if (!$user->authorise('core.manage.contracts', 'com_contractor')) {
            throw new \Exception(\Joomla\CMS\Language\Text::_('JERROR_ALERTNOAUTHOR'), 403);
        }

        $isNew = ($this->item->id == 0);
        $this->addToolbar($isNew);
        parent::display($tpl);
    }

    protected function addToolbar(bool $isNew): void
    {
        Factory::getApplication()->input->set('hidemainmenu', true);
        $title = $isNew ? \Joomla\CMS\Language\Text::_('COM_CONTRACTOR_CONTRACT_NEW') : \Joomla\CMS\Language\Text::_('COM_CONTRACTOR_CONTRACT_EDIT');
        ToolbarHelper::title("Contractor - {$title}", 'file');
        
        ToolbarHelper::apply('contract.apply', 'JTOOLBAR_APPLY');
        ToolbarHelper::save('contract.save', 'JTOOLBAR_SAVE');
        ToolbarHelper::cancel('contract.cancel', 'JTOOLBAR_CLOSE');

        if (!$isNew) {
            ToolbarHelper::custom('contract.email', 'mail', 'mail', 'COM_CONTRACTOR_BTN_SEND_MESSAGE', false);
        }
    }
}