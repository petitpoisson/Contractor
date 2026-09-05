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
use Joomla\Filesystem\File;
use Joomla\Filesystem\Folder;

class ClientModel extends AdminModel
{
    public function getTable($type = 'Client', $prefix = 'Administrator', $config = [])
    {
        return parent::getTable($type, $prefix, $config);
    }

    public function getForm($data = [], $loadData = true)
    {
        return $this->loadForm('com_contractor.client', 'client', ['control' => 'jform', 'load_data' => $loadData]);
    }

    protected function loadFormData()
    {
        $data = $this->getState('client.data', []);
        if (empty($data)) {
            $data = $this->getItem();
        }
        return $data;
    }

    /**
     * Save function override to add writeLog() 
     */
    public function save($data)
    {
        $isNew  = empty($data['id']);
        $result = parent::save($data);
        $userId = \Joomla\CMS\Factory::getApplication()->getIdentity()->id;

        if ($result) {
            $clientId = (int) $this->getState($this->getName() . '.id');
            $action = $isNew ? "created" : "updated";
            $desc   = "Joomla user ({$userId}) {$action} client {$data['client_name']} (id {$clientId})";
            
            \XavierSpirlet\Component\Contractor\Administrator\Helper\ContractorHelper::writeLog($clientId, 0, $desc);
        } else {
            $clientId = $isNew ? 'NEW' : (int) $data['id'];
            $errorMsg = $this->getError() ?: 'unknown error';
            $clientName = $isNew ? 'NEW client' : "client {$data['client_name']}";
            \XavierSpirlet\Component\Contractor\Administrator\Helper\ContractorHelper::writeLog(null, 2, "Joomla user ({$userId}) tried to save {$clientName} (id {$clientId}) information but it failed: {$errorMsg}");
        }
        return $result;
    }

    public function delete(&$pks)
    {
        $userId = \Joomla\CMS\Factory::getApplication()->getIdentity()->id;
        $db     = $this->getDatabase();

        // Fetch client names before deletion to include them in the log
        $pksArray = array_map('intval', (array) $pks);
        $query    = $db->getQuery(true)
                       ->select($db->quoteName(['id', 'client_name']))
                       ->from($db->quoteName('#__ctr_client'))
                       ->where($db->quoteName('id') . ' IN (' . implode(',', $pksArray) . ')');
        
        $clients = $db->setQuery($query)->loadObjectList('id');

        // Proceed with standard Joomla deletion
        $result = parent::delete($pks);

        if ($result) {
            foreach ($pksArray as $pk) {
                $name = isset($clients[$pk]) ? $clients[$clients[$pk]->id]->client_name : 'Unknown';
                $desc = "Joomla user ({$userId}) deleted client {$name} (id {$id})";
                \XavierSpirlet\Component\Contractor\Administrator\Helper\ContractorHelper::writeLog(null, 1, $desc);
            }
        }

        return $result;
    }

    public function getItem($pk = null)
    {
        $item = parent::getItem($pk);

        if ($item && $item->id) {
            $db = $this->getDatabase();
            
            $queryContracts = $db->getQuery(true);
            $queryContracts->select('*')->from($db->quoteName('#__ctr_contract'))->where($db->quoteName('client_id') . ' = ' . (int) $item->id);
            $db->setQuery($queryContracts);
            $item->contracts = $db->loadObjectList();

            $queryLogs = $db->getQuery(true);
            $queryLogs->select($db->quoteName(['timedate', 'level', 'description']))->from($db->quoteName('#__ctr_log'))->where($db->quoteName('client_id') . ' = ' . (int) $item->id)->order($db->quoteName('timedate') . ' DESC');
            $db->setQuery($queryLogs);
            $item->logs = $db->loadObjectList();

            // Fetch Invoices
            $queryInvoices = $db->getQuery(true);
            $queryInvoices->select('*')->from($db->quoteName('#__ctr_invoices'))
                          ->where($db->quoteName('client_id') . ' = ' . (int) $item->id)
                          ->order($db->quoteName('invoicedate') . ' DESC, ' . $db->quoteName('id') . ' DESC');
            $db->setQuery($queryInvoices);
            $item->invoices = $db->loadObjectList();
            
        } else {
            $item->contracts = [];
            $item->logs = [];
            $item->invoices = [];
        }

        return $item;
    }

    /**
     * Add an invoice and upload the associated PDF file
     * * @param int    $clientId The client ID
     * @param string $date     The invoice date
     * @param string $ref      The invoice reference
     * @param string $link     An optional external link
     * @param array  $file     The uploaded file array from $app->input->files
     * * @return \stdClass The inserted invoice object
     * @throws \Exception
     */
    public function addInvoice($clientId, $date, $ref, $link, $file)
    {
        $db = $this->getDatabase();

        if (!$clientId || empty($ref)) {
            throw new \Exception('Missing Client ID or Reference');
        }

        $pdfLink = $link;

        // Handle file upload if present using Joomla 5 Filesystem
        if ($file && $file['error'] == 0) {
            $uploadDirRel = 'images/com_contractor/invoices/client_' . $clientId;
            $uploadDirAbs = JPATH_ROOT . '/' . $uploadDirRel;
            
            if (!Folder::exists($uploadDirAbs)) {
                Folder::create($uploadDirAbs);
            }
            
            $ext = strtolower(File::getExt($file['name']));
            if ($ext === 'pdf') {
                $safeName   = File::makeSafe($file['name']);
                $uniqueName = uniqid() . '_' . $safeName;
                $dest       = $uploadDirAbs . '/' . $uniqueName;
                
                if (File::upload($file['tmp_name'], $dest)) {
                    $pdfLink = $uploadDirRel . '/' . $uniqueName;
                } else {
                    throw new \Exception('File upload failed due to server permissions.');
                }
            } else {
                throw new \Exception('Only PDF files are allowed.');
            }
        }

        // Insert into Database
        $obj = new \stdClass();
        $obj->client_id   = $clientId;
        $obj->invoicedate = $date ?: date('Y-m-d');
        $obj->reference   = $ref;
        $obj->pdf_link    = $pdfLink;

        $db->insertObject('#__ctr_invoices', $obj, 'id');

        // Log the action
        $userId = \Joomla\CMS\Factory::getApplication()->getIdentity()->id;
        $desc   = "Joomla user ({$userId}) added invoice ({$ref}) for client id {$clientId}";
        \XavierSpirlet\Component\Contractor\Administrator\Helper\ContractorHelper::writeLog($clientId, 0, $desc);

        return $obj;
    }

    /**
     * Delete an invoice and its associated file
     * * @param int $id The ID of the invoice to delete
     * @return boolean
     * @throws \Exception
     */
    public function deleteInvoice($id)
    {
        $db = $this->getDatabase();
        $id = (int) $id;

        if (!$id) {
            throw new \Exception('Invalid Invoice ID.');
        }

        $query  = $db->getQuery(true)
                     ->select(['client_id', 'reference', 'pdf_link'])
                     ->from('#__ctr_invoices')
                     ->where('id = ' . $id);
        $oldInv = $db->setQuery($query)->loadObject();
        
        if ($oldInv && strpos($oldInv->pdf_link, 'images/com_contractor/invoices/') === 0) {
            $filePath = JPATH_ROOT . '/' . ltrim($oldInv->pdf_link, '/');
            if (File::exists($filePath)) {
                File::delete($filePath);
            }
        }

        // Delete from Database
        $query = $db->getQuery(true)->delete('#__ctr_invoices')->where('id = ' . $id);
        $db->setQuery($query)->execute();

        if ($oldInv) {
            $userId = \Joomla\CMS\Factory::getApplication()->getIdentity()->id;
            $desc   = "Joomla user ({$userId}) deleted invoice ({$oldInv->reference}) for client id {$oldInv->client_id}";
            \XavierSpirlet\Component\Contractor\Administrator\Helper\ContractorHelper::writeLog($oldInv->client_id, 1, $desc);
        }

        return true;
    }
}