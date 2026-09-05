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

namespace XavierSpirlet\Component\Contractor\Administrator\View\Config;

\defined('_JEXEC') or die;

use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Toolbar\ToolbarHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Factory;

class HtmlView extends BaseHtmlView
{
    public $form;

    public function display($tpl = null)
    {
        // ACL security
        $app  = Factory::getApplication();
        $user = $app->getIdentity();
        if (!$user->authorise('core.manage.config', 'com_contractor')) {
            throw new \Exception(\Joomla\CMS\Language\Text::_('JERROR_ALERTNOAUTHOR'), 403);
        }

        $this->form = $this->get('Form');

        // Render the top toolbar buttons
        $this->addToolbar();

        return parent::display($tpl);
    }

    protected function addToolbar()
    {
        ToolbarHelper::title(Text::_('COM_CONTRACTOR_CONFIGURATION'), 'options');

        // Apply task keeps the user on this edit page
        ToolbarHelper::apply('config.apply', 'JTOOLBAR_APPLY');
        
        // Save task saves and redirects to the dashboard ($view_list target)
        ToolbarHelper::save('config.save', 'JTOOLBAR_SAVE');
        
        // Cancel task aborts and redirects to the dashboard ($view_list target)
        ToolbarHelper::cancel('config.cancel', 'JTOOLBAR_CANCEL');

        ToolbarHelper::preferences('com_contractor');
    }
}