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

namespace XavierSpirlet\Component\Contractor\Administrator\Model;

\defined('_JEXEC') or die;

use Joomla\CMS\MVC\Model\AdminModel;
use Joomla\CMS\Factory;
use XavierSpirlet\Component\Contractor\Administrator\Helper\ContractorHelper;

class ContractModel extends AdminModel
{
    /**
     * Overwrite save() to empty lastmsg if valid_thru is modified.
     */
    public function save($data)
    {
        $db = $this->getDatabase();
        
        // Is this an update and not a new contract ?
        if (!empty($data['id'])) {
            // Get old valid_thru
            $query = $db->getQuery(true)
                ->select($db->quoteName('valid_thru'))
                ->from($db->quoteName('#__ctr_contract'))
                ->where($db->quoteName('id') . ' = ' . (int) $data['id']);
            $db->setQuery($query);
            $oldDate = $db->loadResult();

            // If new date is différent, force reinit lastmsg
            if ($oldDate && $data['valid_thru'] !== $oldDate) {
                $data['lastmsg'] = null; 
            }
        }

        return parent::save($data);
    }

    public function getForm($data = [], $loadData = true)
    {
        $form = $this->loadForm('com_contractor.contract', 'contract', [
            'control'   => 'jform',
            'load_data' => $loadData
        ]);
        return empty($form) ? false : $form;
    }

    protected function loadFormData()
    {
        $app  = Factory::getApplication();
        
        // Check if there is data in the session (e.g., after a failed save attempt)
        $data = $app->getUserState('com_contractor.edit.contract.data', []);

        // If no session data, load it from the database
        if (empty($data)) {
            $data = $this->getItem();

            // --- DEFAULT VALUES FOR NEW CONTRACTS ---
            // If ID is 0, we are creating a new contract
            if ($data->id == 0) {
                // Use Joomla's Date factory to respect the website's configured timezone
                $data->date_start = Factory::getDate()->format('Y-m-d');
                
                // Pre-fill client_id if passed in the URL request
                $reqClientId = $app->input->getInt('client_id', 0);
                if ($reqClientId > 0) {
                    $data->client_id = $reqClientId;
                }
            }
        }

        return $data;
    }

// --- AJAX METHODS FOR CONTRACT LINES ---

    /**
     * Retrieves a single contract line by its ID.
     */
    public function getLine(int $id): ?object
    {
        $db    = $this->getDatabase();
        $query = $db->getQuery(true)
            ->select('*')
            ->from($db->quoteName('#__ctr_contract_line'))
            ->where($db->quoteName('id') . ' = ' . $id);
            
        $db->setQuery($query);
        return $db->loadObject();
    }
    /**
     * Retrieves all lines for a specific contract.
     */
    public function getLines(int $contractId): array
    {
        $db    = $this->getDatabase();
        $query = $db->getQuery(true)
            ->select(['id', 'description', 'price'])
            ->from($db->quoteName('#__ctr_contract_line'))
            ->where($db->quoteName('contract_id') . ' = ' . $contractId)
            ->order('id ASC');
            
        $db->setQuery($query);
        return $db->loadObjectList() ?: [];
    }

    /**
     * Saves (inserts or updates) a line.
     * Note: price is strictly an integer based on the SQL schema.
     */
    public function saveLine(int $id, int $contractId, string $description, int $price): ?object
    {
        $db = $this->getDatabase();
        $line = new \stdClass();
        $line->contract_id = $contractId;
        $line->description = $description;
        $line->price       = $price; // Handled as INT

        if ($id > 0) {
            $line->id = $id;
            $db->updateObject('#__ctr_contract_line', $line, 'id');
        } else {
            $db->insertObject('#__ctr_contract_line', $line, 'id');
        }
        
        // Fetch missing info for logging
        $userId = \Joomla\CMS\Factory::getApplication()->getIdentity()->id;
        $contract = $this->getItem($contractId);
        $clientId = (int)($contract->client_id ?? 0);
        $contractName = $contract->name ?? 'Unknown';

        $action = ($id === 0) ? "added" : "updated";
        ContractorHelper::writeLog($clientId ?: null, 0, "Joomla user (id {$userId}) {$action} line ({$description}) in contract {$contractName} (id {$contractId})");

        return $line;
    }

    /**
     * Deletes a line.
     */
    public function deleteLine(int $id): bool
    {
        // Fetch the line first to have its details for the log
        $line = $this->getLine($id);
        
        // If line doesn't exist, exit early
        if (!$line) {
            return false;
        }

        // Fetch contract details for logging
        $contract = $this->getItem($line->contract_id);
        $clientId = (int)($contract->client_id ?? 0);
        $contractName = $contract->name ?? 'Unknown';
        $userId = \Joomla\CMS\Factory::getApplication()->getIdentity()->id;

        $db    = $this->getDatabase();
        $query = $db->getQuery(true)
            ->delete($db->quoteName('#__ctr_contract_line'))
            ->where($db->quoteName('id') . ' = ' . $id);
        $db->setQuery($query);

        if ($db->execute()) {
            ContractorHelper::writeLog($clientId ?: null, 1, "Joomla user (id {$userId}) deleted line ({$line->description}) from contract {$contractName} (id {$line->contract_id})");
            return true;
        } else {
            ContractorHelper::writeLog($clientId ?: null, 2, "Joomla user (id {$userId}) FAILED to delete line ({$line->description}) from contract {$contractName} (id {$line->contract_id})");
            return false;
        }
    }

    public function deleteLinesByContract($contractId) {

        $db = $this->getDatabase();
        $query = $db->getQuery(true)
            ->select($db->quoteName('id'))
            ->from($db->quoteName('#__ctr_contract_line')) 
            ->where($db->quoteName('contract_id') . ' = ' . (int)$contractId);
        $db->setQuery($query);
        $lns=$db->loadObjectList() ?: [];
        foreach( (array) $lns as $ln) {
            $this->deleteLine($ln->id); 
        }
        return true;
    }
}