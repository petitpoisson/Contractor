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

use Joomla\CMS\MVC\Model\FormModel;
use Joomla\CMS\Factory;
use Joomla\Database\DatabaseInterface;
use XavierSpirlet\Component\Contractor\Administrator\Helper\ContractorHelper;

class ConfigModel extends FormModel
{
    /**
     * Overrides getTable to prevent Joomla from looking for a ConfigTable.php file.
     * The native FormController will adapt and let us use our custom save() method.
     */
    public function getTable($type = 'Config', $prefix = 'Administrator', $config = [])
    {
        return false;
    }

    public function getForm($data = [], $loadData = true)
    {
        $form = $this->loadForm('com_contractor.config', 'config', ['control' => 'jform', 'load_data' => $loadData]);
        return $form;
    }

    protected function loadFormData()
    {
        // Fetch current configurations from database to populate the form fields
        $db    = Factory::getContainer()->get(DatabaseInterface::class);
        $query = $db->getQuery(true)
                    ->select($db->quoteName(['item', 'value']))
                    ->from($db->quoteName('#__ctr_config'));

        $db->setQuery($query);
        $results = $db->loadObjectList();

        $data = [];
        if ($results) {
            foreach ($results as $row) {
                $data[$row->item] = $row->value;
            }
        }

        // Get the version through the helper, as we don't write it in the DB... 
        $config = ContractorHelper::getConfig();
        $data["version"] = $config["version"];

        return $data;
    }

    /**
     * Custom save method to handle row-by-row key/value configuration updates.
     *
     * @param   array  $data  The submitted form data.
     *
     * @return  bool   True on success, false on failure.
     */
    public function save($data)
    {
        $db     = Factory::getContainer()->get(DatabaseInterface::class);
        $userId = Factory::getApplication()->getIdentity()->id;

        foreach ($data as $key => $value) {
            // Prevent writing the version in the DB
            if ($key === 'version') {
                continue;
            }

            $query = $db->getQuery(true)
                        ->update($db->quoteName('#__ctr_config'))
                        ->set($db->quoteName('value') . ' = ' . $db->quote($value))
                        ->where($db->quoteName('item') . ' = ' . $db->quote($key));

            $db->setQuery($query);

            try {
                $db->execute();
            } catch (\Exception $e) {
                // Catch SQL error, log the failure, and abort the save process
                $errorMsg = $e->getMessage();
                
                ContractorHelper::writeLog(
                    null, 
                    2, 
                    "Joomla user ({$userId}) tried to save configuration but it failed: {$errorMsg}"
                );
                
                $this->setError($errorMsg);
                return false;
            }
        }

        // If we reach this point, all configuration items were saved successfully
        ContractorHelper::writeLog(
            null, 
            0, 
            "Joomla user ({$userId}) updated system configuration."
        );

        return true;
    }
    /**
     * Resets the lastmsg field to NULL for all records in the contract table.
     * * @return bool True on success, false on failure.
     */
    public function resetWarningDelays()
    {
        $db = Factory::getContainer()->get(DatabaseInterface::class);
        $query = $db->getQuery(true)
            ->update($db->quoteName('#__ctr_contract'))
            ->set($db->quoteName('lastmsg') . ' = NULL');

        $db->setQuery($query);

        try {
            $db->execute();
            ContractorHelper::writeLog(
                null, 
                1, 
                "Warning messages delays have been reset"
            );
            return true;
        } catch (\Exception $e) {
            ContractorHelper::writeLog(
                null, 
                2, 
                'There was an error trying to reset the warning messages delays: ' . $e->getMessage()
            );
            return false;
        }
    }
}