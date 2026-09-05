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

use Joomla\CMS\MVC\Model\BaseDatabaseModel;

class DashboardModel extends BaseDatabaseModel
{
    /**
     * Retrieves global statistics for the dashboard.
     *
     * @param   int  $warnDays  The threshold in days for contracts in warning status.
     * @return  \stdClass       Object containing the counts.
     */
    public function getStats(int $warnDays = 30): \stdClass
    {
        $db    = $this->getDatabase();
        $stats = new \stdClass();

        // Count total contracts
        $queryContracts = $db->getQuery(true)
                             ->select('COUNT(*)')
                             ->from($db->quoteName('#__ctr_contract'));
        $db->setQuery($queryContracts);
        $stats->contracts = (int) $db->loadResult();

        // Count warning contracts (valid_thru is in the future but within warnDays)
        $queryWarning = $db->getQuery(true)
                           ->select('COUNT(*)')
                           ->from($db->quoteName('#__ctr_contract'))
                           ->where($db->quoteName('valid_thru') . ' >= CURRENT_DATE')
                           ->where($db->quoteName('valid_thru') . ' <= DATE_ADD(CURRENT_DATE, INTERVAL ' . (int) $warnDays . ' DAY)');
        $db->setQuery($queryWarning);
        $stats->warningContracts = (int) $db->loadResult();

        // Count expired contracts (valid_thru is in the past)
        $queryExpired = $db->getQuery(true)
                           ->select('COUNT(*)')
                           ->from($db->quoteName('#__ctr_contract'))
                           ->where($db->quoteName('valid_thru') . ' < CURRENT_DATE');
        $db->setQuery($queryExpired);
        $stats->expiredContracts = (int) $db->loadResult();

        // Count total clients
        $queryClients = $db->getQuery(true)
                           ->select('COUNT(*)')
                           ->from($db->quoteName('#__ctr_client'));
        $db->setQuery($queryClients);
        $stats->clients = (int) $db->loadResult();

        // Count total tags
        $queryTags = $db->getQuery(true)
                        ->select('COUNT(*)')
                        ->from($db->quoteName('#__ctr_tags'));
        $db->setQuery($queryTags);
        $stats->tags = (int) $db->loadResult();

        // Count total log lines
        $queryTags = $db->getQuery(true)
                        ->select('COUNT(id)')
                        ->from($db->quoteName('#__ctr_log'));
        $db->setQuery($queryTags);
        $stats->loglines = (int) $db->loadResult();
        if($stats->loglines > 100000) $stats->loglines=">100000";

        return $stats;
    }

    /**
     * Retrieves the latest logs from the database.
     *
     * @param   int    $limit  Maximum number of logs to retrieve.
     * @return  array          List of log objects.
     */
    public function getLogs(int $limit = 20): array
    {
        $db    = $this->getDatabase();
        $query = $db->getQuery(true)
                    ->select('a.*, c.client_name AS pname')
                    // Correct aliasing inside quoteName()
                    ->from($db->quoteName('#__ctr_log', 'a'))
                    ->join('LEFT', $db->quoteName('#__ctr_client', 'c') . ' ON ' . $db->quoteName('c.id') . ' = ' . $db->quoteName('a.client_id'))
                    // Explicitly define which 'id' to order by
                    ->order($db->quoteName('a.id') . ' DESC');
        $db->setQuery($query, 0, $limit);

        return $db->loadObjectList() ?: [];
    }
}