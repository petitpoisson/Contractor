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

\defined('_JEXEC') or die;

use Joomla\CMS\Table\Table;
use Joomla\CMS\Access\Rules;
use Joomla\CMS\Factory;
use Joomla\Database\DatabaseInterface;
use Joomla\CMS\Installer\Installer;
use Joomla\Filesystem\Folder;
use Joomla\Filesystem\File;
use Joomla\CMS\Language\Text;

/**
 * Installation script for the Contractor component
 */
class com_contractorInstallerScript
{
    /**
     * Method executed after install, update or discover_install
     */
    public function postflight($type, $parent)
    {
        // --- NEW: Specific message for uninstallation ---
        if ($type === 'uninstall') {
            echo '<div class="alert alert-info mt-3">';
            echo '<h4 class="alert-heading">' . Text::_('COM_CONTRACTOR_INSTALL_UNINSTALL_TITLE') . '</h4>';
            echo '<p>' . Text::_('COM_CONTRACTOR_INSTALL_UNINSTALL_DESC') . '</p>';
            echo '</div>';
            return;
        }

        $aclStatus = '';
        $taskStatus = '';
        $pluginsStatus = '';
        
        // Apply default ACL only on fresh installs
        if ($type === 'install' || $type === 'discover_install') {
            $this->setDefaultACL();
            $aclStatus = '<li>&#10004; ' . Text::_('COM_CONTRACTOR_INSTALL_ACL_OK') . '</li>';
        }

        // --- NEW: Physical installation of plugins and modules from the ZIP ---
        if ($type === 'install' || $type === 'update') {
            $taskOk = $this->installBundledPlugin($parent, 'task', 'contractorwarning');
            $iconOk = $this->installBundledPlugin($parent, 'quickicon', 'contractorshortcut');
            $modOk  = $this->installBundledModule($parent, 'mod_contractor_client');
            
            if ($taskOk && $iconOk && $modOk) {
                $pluginsStatus = '<li>&#10004; ' . Text::_('COM_CONTRACTOR_INSTALL_EXT_OK') . '</li>';
            } else {
                $pluginsStatus = '<li>&#10060; ' . Text::_('COM_CONTRACTOR_INSTALL_EXT_ERROR') . '</li>';
            }
        }
        
        // Create the actual scheduled task automatically
        if ($this->createSchedulerTask()) {
            $taskStatus = '<li>&#10004; ' . Text::_('COM_CONTRACTOR_INSTALL_TASK_OK') . '</li>';
        } else {
            $taskStatus = '<li>&#10060; ' . Text::_('COM_CONTRACTOR_INSTALL_TASK_ERROR') . '</li>';
        }

        // Display explicit UI feedback within the Joomla installer message box
        echo '<div class="alert alert-success mt-3">';
        echo '<h4 class="alert-heading"><span class="fas fa-file-contract"></span>&nbsp;' . Text::_('COM_CONTRACTOR_INSTALL_SUCCESS_TITLE') . '</h4>';
        echo '<ul style="list-style-type: none; padding-left: 0; margin-bottom: 0;">';
        echo '<li>&#10004; ' . Text::_('COM_CONTRACTOR_INSTALL_CORE_OK') . '</li>';
        echo '<li>&#10004; ' . Text::_('COM_CONTRACTOR_INSTALL_DB_OK') . '</li>';
        echo $pluginsStatus;
        echo $taskStatus;
        echo $aclStatus;
        echo '</ul>';
        echo '</div>';
    }

    /**
     * Force the installation of a plugin present in the component's ZIP
     */
    private function installBundledPlugin($parent, $folder, $element)
    {
        try {
            // Retrieve the path of the temporary folder where Joomla extracted the ZIP
            $src = $parent->getParent()->getPath('source');
            $pluginPath = $src . '/plugins/' . $folder . '/' . $element;
            
            if (is_dir($pluginPath)) {
                $installer = new \Joomla\CMS\Installer\Installer;
                
                // Strict Joomla 5/6 dependency injection
                $db = \Joomla\CMS\Factory::getContainer()->get(\Joomla\Database\DatabaseInterface::class);
                
                // Use the DatabaseAwareTrait method
                if (method_exists($installer, 'setDatabase')) {
                    $installer->setDatabase($db);
                } else {
                    $installer->setDbo($db); // Ultra-safe fallback
                }
                
                // Install the plugin
                $result = $installer->install($pluginPath);
                
                if ($result) {
                    // Enable the plugin
                    $this->enablePlugin($folder, $element);
                    return true;
                } else {
                    throw new \RuntimeException("Internal installer returned false.");
                }
            } else {
                throw new \RuntimeException("Path not found: " . $pluginPath);
            }
        } catch (\Throwable $e) {
            \Joomla\CMS\Factory::getApplication()->enqueueMessage(Text::sprintf('COM_CONTRACTOR_INSTALL_PLUGIN_ERROR', $element, $e->getMessage()), 'error');
        }
        
        return false;
    }

    /**
     * Force the installation of a module present in the component's ZIP
     */
    private function installBundledModule($parent, $element)
    {
        try {
            $src = $parent->getParent()->getPath('source');
            $modulePath = $src . '/modules/' . $element;
            
            if (is_dir($modulePath)) {
                $installer = new \Joomla\CMS\Installer\Installer;
                $db = \Joomla\CMS\Factory::getContainer()->get(\Joomla\Database\DatabaseInterface::class);
                
                if (method_exists($installer, 'setDatabase')) {
                    $installer->setDatabase($db);
                } else {
                    $installer->setDbo($db);
                }
                
                $result = $installer->install($modulePath);
                
                if ($result) {
                    return true;
                } else {
                    throw new \RuntimeException("Internal installer returned false.");
                }
            } else {
                throw new \RuntimeException("Path not found: " . $modulePath);
            }
        } catch (\Throwable $e) {
            \Joomla\CMS\Factory::getApplication()->enqueueMessage(Text::sprintf('COM_CONTRACTOR_INSTALL_MODULE_ERROR', $element, $e->getMessage()), 'error');
        }
        
        return false;
    }

    /**
     * Force the uninstallation of a linked plugin
     */
    private function uninstallBundledPlugin($folder, $element)
    {
        try {
            $db = \Joomla\CMS\Factory::getContainer()->get(\Joomla\Database\DatabaseInterface::class);
            
            $query = $db->getQuery(true)
                ->select($db->quoteName('extension_id'))
                ->from($db->quoteName('#__extensions'))
                ->where($db->quoteName('type') . ' = ' . $db->quote('plugin'))
                ->where($db->quoteName('folder') . ' = ' . $db->quote($folder))
                ->where($db->quoteName('element') . ' = ' . $db->quote($element));
            
            $db->setQuery($query);
            $id = (int) $db->loadResult();
            
            if ($id > 0) {
                $installer = new \Joomla\CMS\Installer\Installer;
                
                if (method_exists($installer, 'setDatabase')) {
                    $installer->setDatabase($db);
                } else {
                    $installer->setDbo($db);
                }
                
                $installer->uninstall('plugin', $id);
            }
        } catch (\Throwable $e) {
            // Silent failure, we do not block component uninstallation
        }
    }

    /**
     * Force the uninstallation of a linked module
     */
    private function uninstallBundledModule($element, $clientId = 0)
    {
        try {
            $db = \Joomla\CMS\Factory::getContainer()->get(\Joomla\Database\DatabaseInterface::class);
            
            $query = $db->getQuery(true)
                ->select($db->quoteName('extension_id'))
                ->from($db->quoteName('#__extensions'))
                ->where($db->quoteName('type') . ' = ' . $db->quote('module'))
                ->where($db->quoteName('element') . ' = ' . $db->quote($element))
                ->where($db->quoteName('client_id') . ' = ' . (int) $clientId);
            
            $db->setQuery($query);
            $id = (int) $db->loadResult();
            
            if ($id > 0) {
                $installer = new \Joomla\CMS\Installer\Installer;
                
                if (method_exists($installer, 'setDatabase')) {
                    $installer->setDatabase($db);
                } else {
                    $installer->setDbo($db);
                }
                
                $installer->uninstall('module', $id);
            }
        } catch (\Throwable $e) {
            // Silent failure, we do not block component uninstallation
        }
    }

    /**
     * Enables the bundled plugins
     */
    private function enablePlugin($folder, $element)
    {
        $db = Factory::getContainer()->get(DatabaseInterface::class);
        $query = $db->getQuery(true)
            ->update($db->quoteName('#__extensions'))
            ->set($db->quoteName('enabled') . ' = 1')
            ->where($db->quoteName('type') . ' = ' . $db->quote('plugin'))
            ->where($db->quoteName('folder') . ' = ' . $db->quote($folder))
            ->where($db->quoteName('element') . ' = ' . $db->quote($element));
        
        try {
            $db->setQuery($query)->execute();
        } catch (\Exception $e) {
            // Fails silently
        }
    }

    /**
     * Instantiates the Task in Joomla's Task Scheduler so it runs automatically
     */
    private function createSchedulerTask()
    {
        try {
            $db = Factory::getContainer()->get(DatabaseInterface::class);
            
            $query = $db->getQuery(true)
                ->select($db->quoteName('id'))
                ->from($db->quoteName('#__scheduler_tasks'))
                ->where($db->quoteName('type') . ' = ' . $db->quote('contractorwarning.check')); 
            
            $db->setQuery($query);
            $exists = $db->loadResult();
            
            if (!$exists) {
                // Définition du JSON exact attendu par Joomla pour "Tous les jours à 01:00"
                $executionRules = json_encode([
                    "rule-type" => "1", // 1 = Type "Cron" (Routine) dans Joomla
                    "Cron" => [
                        "time" => "01:00" // Heure d'exécution
                    ],
                    "Interval" => [
                        "interval" => "1",
                        "unit" => "h"
                    ]
                ]);
                
                $now = Factory::getDate()->toSql();
                
                // On fixe la prochaine exécution à demain 01:00 AM par défaut
                $nextExecution = Factory::getDate('tomorrow 01:00:00')->toSql();
                
                $columns = ['title', 'state', 'type', 'execution_rules', 'params', 'next_execution', 'created'];
                $values = [
                    $db->quote('Contractor - Check Expiring Contracts'), 
                    1,                                                 
                    $db->quote('contractorwarning.check'),                
                    $db->quote($executionRules),                         
                    $db->quote('{}'),                                    
                    $db->quote($nextExecution),                          
                    $db->quote($now)                                     
                ];
                
                $insert = $db->getQuery(true)
                    ->insert($db->quoteName('#__scheduler_tasks'))
                    ->columns($db->quoteName($columns))
                    ->values(implode(', ', $values));
                    
                $db->setQuery($insert)->execute();
            }
            return true; 
        } catch (\Throwable $e) {
            Factory::getApplication()->enqueueMessage(Text::sprintf('COM_CONTRACTOR_INSTALL_SCHEDULER_ERROR', $e->getMessage()), 'error');
            return false;
        }
    }

    /**
     * Delete schedulerTask (created at install)
     */
    private function removeSchedulerTask()
    {
        $db = Factory::getContainer()->get(DatabaseInterface::class);
        $query = $db->getQuery(true)
            ->delete($db->quoteName('#__scheduler_tasks'))
            ->where($db->quoteName('type') . ' = ' . $db->quote('contractorwarning.check')); 
            
        try {
            $db->setQuery($query)->execute();
        } catch (\Exception $e) {}
    }

    /**
     * Injects default permissions into the assets table
     */
    private function setDefaultACL()
    {
        $adminGroupId = 7;
        $assetName    = 'com_contractor';
        
        $asset = Table::getInstance('Asset');

        if ($asset->loadByName($assetName)) {
            $rules = json_decode($asset->rules, true) ?: [];

            $rules['core.options'][$adminGroupId] = 0;
            $rules['core.manage.clients'][$adminGroupId]   = 1;
            $rules['core.manage.contracts'][$adminGroupId] = 1;
            $rules['core.manage.tags'][$adminGroupId]      = 1;
            $rules['core.manage.logs'][$adminGroupId]      = 1;
            $rules['core.manage.config'][$adminGroupId]    = 1;

            $asset->rules = (string) new Rules($rules);
            $asset->store();
        }
    }

    /**
     * Component uninstallation
     */
    public function uninstall($parent)
    {
        $this->removeSchedulerTask();
        
        // Cleanly remove plugins and module upon uninstallation
        $this->uninstallBundledPlugin('task', 'contractorwarning');
        $this->uninstallBundledPlugin('quickicon', 'contractorshortcut');
        $this->uninstallBundledModule('mod_contractor_client', 0);

        // Cleanly remove the physical upload directory and all its contents
        $uploadDir = JPATH_ROOT . '/images/com_contractor';
        if (Folder::exists($uploadDir)) {
            try {
                if (!Folder::delete($uploadDir)) {
                    Factory::getApplication()->enqueueMessage(Text::sprintf('COM_CONTRACTOR_UNINSTALL_FOLDER_WARN', $uploadDir), 'warning');
                }
            } catch (\Throwable $e) {
                Factory::getApplication()->enqueueMessage(Text::sprintf('COM_CONTRACTOR_UNINSTALL_FOLDER_ERROR', $e->getMessage(), $uploadDir), 'warning');
            }
        }
    }

    /**
     * Method executed specifically during an update
     */
    public function update($parent)
    {
        // On appelle notre fonction de nettoyage
        $this->cleanupObsoleteFiles();
    }

    /**
     * Clean up old files and folders that are no longer needed
     */
    private function cleanupObsoleteFiles()
    {
        // Liste des fichiers à supprimer (commentée/vide pour l'instant)
        $obsoleteFiles = [
            // Exemple :
            // JPATH_ADMINISTRATOR . '/components/com_contractor/src/Helper/OldHelper.php',
            // JPATH_SITE . '/components/com_contractor/tmpl/old_view.php'
        ];

        // Liste des dossiers entiers à supprimer (commentée/vide pour l'instant)
        $obsoleteFolders = [
            // Exemple :
            // JPATH_SITE . '/components/com_contractor/old_assets'
        ];

        // Suppression des fichiers
        foreach ($obsoleteFiles as $file) {
            if (File::exists($file)) {
                File::delete($file);
            }
        }

        // Suppression des dossiers
        foreach ($obsoleteFolders as $folder) {
            if (Folder::exists($folder)) {
                Folder::delete($folder);
            }
        }
    }
}