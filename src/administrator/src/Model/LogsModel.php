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

use Joomla\CMS\MVC\Model\ListModel;

class LogsModel extends ListModel
{
    public function __construct($config = [])
    {
        if (empty($config['filter_fields'])) {
            $config['filter_fields'] = [
                'timedate', 'a.timedate',
                'level', 'a.level',
                'description', 'a.description',
                'client_name', 'c.client_name'
            ];
        }
        parent::__construct($config);
    }

    protected function populateState($ordering = 'a.timedate', $direction = 'DESC')
    {
        $app = \Joomla\CMS\Factory::getApplication();

        // Force saving date filters into J! state manager (to allow drawer to be open)
        $this->setState('filter.from_date', $app->getUserStateFromRequest($this->context . '.filter.from_date', 'filter_from_date', '', 'string'));
        $this->setState('filter.until_date', $app->getUserStateFromRequest($this->context . '.filter.until_date', 'filter_until_date', '', 'string'));

        parent::populateState($ordering, $direction);
    }

    protected function getListQuery()
    {
        $db    = $this->getDatabase();
        $query = $db->getQuery(true);

        // Select log fields and the associated client name
        $query->select(
            $db->quoteName(['a.id', 'a.timedate', 'a.level', 'a.description', 'a.client_id'])
        );
        $query->select($db->quoteName('c.client_name', 'client_name'));
        
        $query->from($db->quoteName('#__ctr_log', 'a'));
        
        $query->join('LEFT', $db->quoteName('#__ctr_client', 'c') . ' ON ' . $db->quoteName('c.id') . ' = ' . $db->quoteName('a.client_id'));

        $level = $this->getState('filter.level');
        if (is_numeric($level)) {
            $query->where($db->quoteName('a.level') . ' = ' . (int) $level);
        }

        $clientId = $this->getState('filter.client_id');
        if (is_numeric($clientId)) {
            $query->where($db->quoteName('a.client_id') . ' = ' . (int) $clientId);
        }

        $fromDate = $this->getState('filter.from_date');
        if (!empty($fromDate)) {
            $query->where($db->quoteName('a.timedate') . ' >= ' . $db->quote($fromDate . ' 00:00:00'));
        }

        $untilDate = $this->getState('filter.until_date');
        if (!empty($untilDate)) {
            $query->where($db->quoteName('a.timedate') . ' <= ' . $db->quote($untilDate . ' 23:59:59'));
        }

        $search = $this->getState('filter.search');
        if (!empty($search)) {
            $search = $db->quote('%' . str_replace(' ', '%', $db->escape(trim($search), true) . '%'));
            $query->where($db->quoteName('a.description') . ' LIKE ' . $search);
        }

        // Add sorting
        $orderCol  = $this->state->get('list.ordering', 'a.timedate');
        $orderDirn = $this->state->get('list.direction', 'DESC');
        $query->order($db->escape($orderCol) . ' ' . $db->escape($orderDirn));

        return $query;
    }
}