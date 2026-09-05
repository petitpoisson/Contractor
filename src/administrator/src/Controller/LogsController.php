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
use Joomla\CMS\Language\Text;
use XavierSpirlet\Component\Contractor\Administrator\Helper\ContractorHelper;

class LogsController extends AdminController
{
    /**
     * Export current selection in CSV
     */
    public function export()
    {
        // Vérification de sécurité
        $this->checkToken('request');

        $model = $this->getModel('Logs');

        // On force la limite à 0 pour ignorer la pagination
        $model->setState('list.start', 0);
        $model->setState('list.limit', 0);

        // --- NOUVEAU : Construction du nom de fichier dynamique ---
        $fromDate  = $model->getState('filter.from_date');
        $untilDate = $model->getState('filter.until_date');

        $fromStr  = empty($fromDate) ? '00000000' : date('Ymd', strtotime($fromDate));
        $untilStr = empty($untilDate) ? date('Ymd') : date('Ymd', strtotime($untilDate));
        
        $filename = "contractorLogs-{$fromStr}-{$untilStr}.csv";

        $items = $model->getItems();
        $userId = $this->app->getIdentity()->id;
        ContractorHelper::writeLog(null, 0, "Joomla user ({$userId}) exported logs from {$fromStr} to {$untilStr}");

        // Cleaning output buffers
        while (ob_get_level()) {
            ob_end_clean();
        }

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Pragma: no-cache');
        header('Expires: 0');

        $output = fopen('php://output', 'w');

        fputs($output, "\xEF\xBB\xBF");

        $headers = ['ID', 'Date / Time', 'Client ID', 'Level', 'Description'];
        fputcsv($output, $headers, ';', '"', '\\');

        // Writing data
        if (!empty($items)) {
            foreach ($items as $item) {
                $levelText = 'Info';
                if ($item->level == 1) {
                    $levelText = 'Warning';
                } elseif ($item->level == 2) {
                    $levelText = 'Error';
                }

                $row = [
                    $item->id,
                    $item->timedate,
                    $item->client_id ?: '0',
                    $levelText,
                    $item->description
                ];
                
                fputcsv($output, $row, ';', '"', '\\');
            }
        }

        fclose($output);
        $this->app->close();
    }
    /**
     * Prunes logs older than the specified date
     */
    public function prune()
    {
        // Security check
        $this->checkToken('request');

        $date = $this->app->input->getString('prune_date', '');
        $userId = $this->app->getIdentity()->id;

        // Validate the date format received from the form
        if (!empty($date) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            $db    = Factory::getContainer()->get('DatabaseDriver');
            $query = $db->getQuery(true);

            // Delete records strictly older than the specified date at 00:00:00
            $query->delete($db->quoteName('#__ctr_log'))
                  ->where($db->quoteName('timedate') . ' < ' . $db->quote($date . ' 00:00:00'));

            try {
                $db->setQuery($query);
                $db->execute();
                $affectedRows = $db->getAffectedRows();
                
                $this->app->enqueueMessage(Text::_('COM_CONTRACTOR_LOG_PROK1').' ' . $affectedRows . ' '.Text::_('COM_CONTRACTOR_LOG_PROK2'), 'success');
                ContractorHelper::writeLog(null, 0, "Joomla user ({$userId}) successfully pruned logs");

            } catch (\Exception $e) {
                $this->app->enqueueMessage(Text::_('COM_CONTRACTOR_LOG_PRKO') . $e->getMessage(), 'error');
                ContractorHelper::writeLog(null, 2, "Joomla user ({$userId}) had an error pruning logs: ". $e->getMessage());
            }
        } else {
            $this->app->enqueueMessage(Text::_('COM_CONTRACTOR_LOG_PRWA'), 'warning');
            ContractorHelper::writeLog(NULL, 1, Text::_('COM_CONTRACTOR_LOG_PRWA'));
        }

        // Redirect back to the logs list
        $this->setRedirect(\Joomla\CMS\Router\Route::_('index.php?option=com_contractor&view=logs', false));
    }
}