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

namespace XavierSpirlet\Component\Contractor\Administrator\View\Contractmsg;

\defined('_JEXEC') or die;

use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Toolbar\ToolbarHelper;
use Joomla\CMS\Factory;

class HtmlView extends BaseHtmlView
{
    public $data;
    public $returnCtx;

    public function display($tpl = null): void
    {
        $app = Factory::getApplication();
        $id  = $app->input->getInt('id', 0);
        $this->returnCtx = $app->input->getString('return', 'contracts');

        // ACL security
        $app  = Factory::getApplication();
        $user = $app->getIdentity();
        if (!$user->authorise('core.manage.contracts', 'com_contractor')) {
            throw new \Exception(\Joomla\CMS\Language\Text::_('JERROR_ALERTNOAUTHOR'), 403);
        }

        $model = $this->getModel();
        $this->data = $model->getMessageData($id);

        if (!$this->data) {
            throw new \Exception(\Joomla\CMS\Language\Text::_('COM_CONTRACTOR_ERROR_CONTRACT_NOT_FOUND'), 404);
        }

        $this->addToolbar();
        parent::display($tpl);
    }

    protected function addToolbar(): void
    {
        ToolbarHelper::title(\Joomla\CMS\Language\Text::_('COM_CONTRACTOR_MSG_VIEW_TITLE'), 'mail');
        ToolbarHelper::custom('contractmsg.send', 'envelope', 'envelope', 'COM_CONTRACTOR_MSG_BTN_SEND', false);
        ToolbarHelper::cancel('contractmsg.cancel', 'JTOOLBAR_CANCEL');
    }
}