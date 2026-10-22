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
use Joomla\Utilities\ArrayHelper;

class ContractsModel extends ListModel
{
    public function __construct($config = [])
    {
        if (empty($config['filter_fields'])) {
            $config['filter_fields'] = [
                'id', 'a.id',
                'published', 'a.published',
                'name', 'a.name',
                'client_id', 'a.client_id',
                'valid_thru', 'a.valid_thru',
                'client_name', 'c.client_name',
                'totprice', 'totprice'
            ];
        }
        parent::__construct($config);
    }

    protected function populateState($ordering = 'a.valid_thru', $direction = 'asc')
    {
        parent::populateState($ordering, $direction);
    }

    protected function getListQuery()
    {
        $db    = $this->getDatabase();
        $query = $db->getQuery(true);

        $query->select(
            $db->quoteName([
                'a.id', 'a.published', 'a.client_id', 'a.name', 'a.valid_thru'
            ])
        );
        $query->select('COUNT(' . $db->quoteName('l.id') . ') AS ' . $db->quoteName('nblines'));
        $query->select('SUM(' . $db->quoteName('l.price') . ') AS ' . $db->quoteName('totprice'));
        $query->select($db->quoteName('c.client_name', 'client_name'));
        
        // Select multiple tags information using GROUP_CONCAT with proper separators for parsing
        $query->select('GROUP_CONCAT(DISTINCT CONCAT(t.value, ' . $db->quote('|||') . ', t.color) SEPARATOR ' . $db->quote(';;;') . ') AS tag_data');
        
        $query->from($db->quoteName('#__ctr_contract', 'a'));
        
        $query->join('LEFT', $db->quoteName('#__ctr_client', 'c') . ' ON ' . $db->quoteName('c.id') . ' = ' . $db->quoteName('a.client_id'));
        $query->join('LEFT', $db->quoteName('#__ctr_contract_line', 'l') . ' ON ' . $db->quoteName('l.contract_id') . ' = ' . $db->quoteName('a.id'));
        
        // Pivot table joins
        $query->join('LEFT', $db->quoteName('#__ctr_contract_tags', 'ct') . ' ON ' . $db->quoteName('ct.contract_id') . ' = ' . $db->quoteName('a.id'));
        $query->join('LEFT', $db->quoteName('#__ctr_tags', 't') . ' ON ' . $db->quoteName('t.id') . ' = ' . $db->quoteName('ct.tag_id'));
        
        $query->group($db->quoteName('a.id'));

        // Filtres
        $published = $this->getState('filter.published');
        if ($published !== null && $published !== '') {
            $query->where($db->quoteName('a.published') . ' = ' . (int) $published);
        }

        // Filter by tag_id (can be an array now from the filter)
        $tagIds = $this->getState('filter.tag_id');
        if (!empty($tagIds)) {
            // If it's a single value from old sessions, convert to array
            if (!is_array($tagIds)) {
                $tagIds = [$tagIds];
            }
            $tagIds = ArrayHelper::toInteger($tagIds);
            
            // Join subquery to ensure we only get contracts that possess AT LEAST ONE of the selected tags
            $query->where($db->quoteName('a.id') . ' IN (SELECT contract_id FROM ' . $db->quoteName('#__ctr_contract_tags') . ' WHERE tag_id IN (' . implode(',', $tagIds) . '))');
        }

        $clientId = $this->getState('filter.client_id');
        if (is_numeric($clientId)) {
            $query->where($db->quoteName('a.client_id') . ' = ' . (int) $clientId);
        }

        $search = $this->getState('filter.search');
        if (!empty($search)) {
            $search = $db->quote('%' . str_replace(' ', '%', $db->escape(trim($search), true) . '%'));
            $query->where('(' . $db->quoteName('a.name') . ' LIKE ' . $search . ')');
        }

        $orderCol  = $this->state->get('list.ordering', 'a.valid_thru');
        $orderDirn = $this->state->get('list.direction', 'ASC');
        $query->order($db->escape($orderCol) . ' ' . $db->escape($orderDirn));

        return $query;
    }
}