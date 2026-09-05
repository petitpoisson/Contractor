<?php
/**
 * @package     Joomla.Site
 * @subpackage  com_contractor
 *
 * @author      Xavier Spirlet (Petitpoisson) <https://www.petitpoisson.be>
 * @copyright   Copyright (C) 2026 Xavier Spirlet. All rights reserved.
 * @license     GNU General Public License version 3 or later; see LICENSE.txt
 * @link        https://www.petitpoisson.be
 */

namespace XavierSpirlet\Component\Contractor\Site\Model;

// No direct access
\defined('_JEXEC') or die;

use Joomla\CMS\MVC\Model\BaseDatabaseModel;
use Joomla\CMS\Factory;
use Joomla\Database\DatabaseInterface;

class ClientModel extends BaseDatabaseModel
{
    /**
     * Retrieves the current logged-in user's mapped client ID.
     * * @return int|null
     */
    private function getClientId(): ?int
    {
        $user = Factory::getApplication()->getIdentity();
        if ($user->guest) {
            return null;
        }

        $db = $this->getDatabase();
        $query = $db->getQuery(true)
            ->select($db->quoteName('id'))
            ->from($db->quoteName('#__ctr_client'))
            ->where($db->quoteName('joomla_user_id') . ' = ' . (int) $user->id)
            ->where($db->quoteName('published') . ' = 1') ;

        $db->setQuery($query);
        return (int) $db->loadResult() ?: null;
    }

    public function getContracts(): array
    {
        $clientId = $this->getClientId();
        if (!$clientId) {
            return [];
        }

        $db = $this->getDatabase();

        // 1. Get all contracts
        $query = $db->getQuery(true)
            ->select('*')
            ->from($db->quoteName('#__ctr_contract'))
            ->where($db->quoteName('client_id') . ' = ' . (int) $clientId)
            ->where($db->quoteName('published') . ' = 1')
            ->order($db->quoteName('valid_thru') . ' DESC');

        $db->setQuery($query);
        $contracts = $db->loadObjectList();

        if (empty($contracts)) {
            return [];
        }

        // 2. Get all lines for these contracts in ONE query
        $contractIds = array_column($contracts, 'id');
        $queryLines = $db->getQuery(true)
            ->select('*')
            ->from($db->quoteName('#__ctr_contract_line'))
            ->where($db->quoteName('contract_id') . ' IN (' . implode(',', $contractIds) . ')');

        $db->setQuery($queryLines);
        $lines = $db->loadObjectList();

        // 3. Map lines to their respective contracts and calculate total
        // Initialize structures
        foreach ($contracts as $contract) {
            $contract->lines = [];
            $contract->total_amount = 0;
        }

        foreach ($lines as $line) {
            foreach ($contracts as $contract) {
                if ($line->contract_id == $contract->id) {
                    $contract->lines[] = $line;
                    $contract->total_amount += $line->price;
                    break;
                }
            }
        }

        return $contracts;
    }

    /**
     * Retrieves all invoices for the current user (Placeholder for future development).
     * * @return array
     */
    public function getInvoices(): array
    {
        $clientId = $this->getClientId();
        if (!$clientId) {
            return [];
        }

        $db = $this->getDatabase();
        
        // Fetch Invoices
        $queryInvoices = $db->getQuery(true);
        $queryInvoices->select('*')->from($db->quoteName('#__ctr_invoices'))
                       ->where($db->quoteName('client_id') . ' = ' . (int) $clientId)
                       ->order($db->quoteName('invoicedate') . ' DESC, ' . $db->quoteName('id') . ' DESC');
        $db->setQuery($queryInvoices);
        $invoices = $db->loadObjectList();

        return $invoices;
    }
}