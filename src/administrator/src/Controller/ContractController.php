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

use Joomla\CMS\MVC\Controller\FormController;
use Joomla\CMS\Response\JsonResponse;
use Joomla\CMS\Language\Text;
use XavierSpirlet\Component\Contractor\Administrator\Helper\ContractorHelper;

/**
 * Contract Controller
 */
class ContractController extends FormController
{
    /**
     * Override save to log creation/updates
     */
    public function save($key = null, $urlVar = null)
    {
        $data = $this->app->input->get('jform', [], 'array');
        $id = (int) ($data['id'] ?? 0);
        $isNew = ($id === 0);
        $clientId = (int) ($data['client_id'] ?? 0);
        
        $result = parent::save($key, $urlVar);
        
        $userId = $this->app->getIdentity()->id;
        $contractName = $data['name'] ?? 'Unknown';
        $contractId = $this->getModel()->getState($this->getName() . '.id');

        if ($result) {
            $action = $isNew ? "created" : "updated";
            ContractorHelper::writeLog($clientId ?: null, 0, "Joomla user ({$userId}) {$action} contract {$contractName} (id {$contractId})");
        }
        
        return $result;
    }

    /**
     * Override delete to log deletions and remove associated lines
     */
    public function delete()
    {
        $pks = $this->app->input->post->get('cid', [], 'array');
        $userId = $this->app->getIdentity()->id;
        $model = $this->getModel('Contract');
        
        foreach ($pks as $pk) {
            $pk = (int)$pk;
            // Fetch contract info before deletion for logging
            $contract = $model->getItem($pk);
            $clientId = (int)($contract->client_id ?? 0);

            // Delete associated lines first (Cascade logic)
            $model->deleteLinesByContract($contract->id);

            // Create an array to pass by reference
            $pkArray = [$pk];
            if ($model->delete($pkArray)) {
                $this->app->enqueueMessage(Text::_('COM_CONTRACTOR_ITEMS_DELETED'));
                ContractorHelper::writeLog($clientId ?: null, 1, "Joomla user (id {$userId}) deleted contract {$contract->name} (id {$contract->id})");
            } else {
                $this->app->enqueueMessage($model->getError(), 'error');
                ContractorHelper::writeLog($clientId ?: null, 2, "Joomla user (id {$userId}) FAILED to delete contract {$contract->name} (id {$contract->id})");
            }
        }
        $this->setRedirect('index.php?option=com_contractor&view=contracts');
    }
        

    public function email()
    {
        $id  = $this->app->input->getInt('id', 0);

        if ($id === 0) {
            $this->app->enqueueMessage(Text::_('COM_CONTRACTOR_SAVE_BEFORE_EMAIL'), 'warning');
            $this->setRedirect(\Joomla\CMS\Router\Route::_('index.php?option=com_contractor&view=contract&layout=edit&id=0', false));
            return false;
        }

        $this->setRedirect(\Joomla\CMS\Router\Route::_('index.php?option=com_contractor&view=contractmsg&id=' . $id . '&return=contract', false));
    }

    public function getLines()
    {
        $contractId = $this->app->input->getInt('contract_id', 0);
        $model = $this->getModel('Contract');
        echo new JsonResponse($model->getLines($contractId));
        $this->app->close();
    }

    public function saveLine()
    {
        $this->checkToken('request');
        $id          = $this->app->input->getInt('line_id', 0);
        $contractId  = $this->app->input->getInt('contract_id', 0);
        $description = $this->app->input->getString('description', '');
        $price       = $this->app->input->getInt('price', 0);

        if ($contractId === 0) {
            echo new JsonResponse(new \Exception('Contract ID missing'), 'Contract must be saved first.', true);
            $this->app->close();
        }

        $model = $this->getModel('Contract');
        $line  = $model->saveLine($id, $contractId, $description, $price);

        echo new JsonResponse($line, 'Line saved successfully.');
        $this->app->close();
    }

    public function deleteLine()
    {
        $this->checkToken('request');
        $id  = $this->app->input->getInt('line_id', 0);

        $model = $this->getModel('Contract');
        
        if ($model->deleteLine($id)) {
            echo new JsonResponse(null, 'Line deleted.');
        } else {
            echo new JsonResponse(new \Exception('Deletion failed'), 'Error deleting line.', true);
        }
        $this->app->close();
    }
}