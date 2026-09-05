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

namespace XavierSpirlet\Component\Contractor\Administrator\Controller;

\defined('_JEXEC') or die;

use Joomla\CMS\MVC\Controller\AdminController;

/**
 * Contracts List Controller
 */
class ContractsController extends AdminController
{
    /**
     * Proxy for getModel.
     *
     * @param   string  $name    The model name.
     * @param   string  $prefix  The class prefix.
     * @param   array   $config  Configuration array for model.
     *
     * @return  \Joomla\CMS\MVC\Model\BaseDatabaseModel
     */
    public function getModel($name = 'Contract', $prefix = 'Administrator', $config = ['ignore_request' => true])
    {
        return parent::getModel($name, $prefix, $config);
    }

    public function email()
    {
        $app = \Joomla\CMS\Factory::getApplication();
        $cids = $app->input->get('cid', [], 'array');

        if (count($cids) !== 1) {
            $app->enqueueMessage(\Joomla\CMS\Language\Text::_('COM_CONTRACTOR_SELECT_EXACTLY_ONE_CONTRACT'), 'warning');
            $this->setRedirect(\Joomla\CMS\Router\Route::_('index.php?option=com_contractor&view=contracts', false));
            return false;
        }

        // Redirect to the new view, passing the selected ID and a return context
        $this->setRedirect(\Joomla\CMS\Router\Route::_('index.php?option=com_contractor&view=contractmsg&id=' . (int) $cids[0] . '&return=contracts', false));
    }
}