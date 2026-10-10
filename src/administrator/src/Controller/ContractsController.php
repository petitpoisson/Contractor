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
    public function duplicate()
    {
        // Check for request forgeries
        $this->checkToken();

        // Get items to duplicate from the request
        $pks = $this->input->post->get('cid', [], 'array');

        try {
            if (empty($pks)) {
                throw new \Exception(\Joomla\CMS\Language\Text::_('JERROR_NO_ITEMS_SELECTED'));
            }

            // We need the singular model to handle table operations properly
            $model = $this->getModel('Contract', 'Administrator', ['ignore_request' => true]);
            $table = $model->getTable();

            foreach ($pks as $pk) {
                $table->reset();
                
                // Load the original item
                if ($table->load($pk)) {
                    // Unset the primary key to create a new record
                    $table->id = 0;
                    
                    // Force unpublished state 
                    $table->published = 0;
                    
                    // Optional: Append " (Copy)" to the name/title if desired
                    $table->name = $table->name . ' (Copy)';

                    // Save the duplicated record
                    if (!$table->store()) {
                        throw new \Exception($table->getError());
                    }
                }
            }

            $this->setMessage(\Joomla\CMS\Language\Text::_('COM_CONTRACTOR_ITEMS_DUPLICATED'));
        } catch (\Exception $e) {
            $this->setMessage($e->getMessage(), 'error');
        }

        $this->setRedirect(\Joomla\CMS\Router\Route::_('index.php?option=com_contractor&view=contracts', false));
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