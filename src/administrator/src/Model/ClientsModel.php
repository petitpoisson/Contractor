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

class ClientsModel extends ListModel
{
    public function __construct($config = [])
    {
        // Add all sortable columns to the filter_fields array
        if (empty($config['filter_fields'])) {
            $config['filter_fields'] = [
                'id', 'a.id',
                'published', 'a.published',
                'client_name', 'a.client_name',
                'company_name', 'a.company_name',
                'email', 'a.email',
                'tag_value', 't.value', 'nbcontract', 'nbinvoices'
            ];
        }
        parent::__construct($config);
    }

    /**
     * Method to auto-populate the model state.
     */
    protected function populateState($ordering = 'a.client_name', $direction = 'asc')
    {
        parent::populateState($ordering, $direction);
    }

    protected function getListQuery()
    {
        $db    = $this->getDatabase();
        $query = $db->getQuery(true);

        $query->select(
            $db->quoteName([
                'a.id', 'a.published', 'a.tag_id', 'a.client_name', 'a.company_name', 'a.email', 'a.date_crea'
            ])
        );
        
        // Use DISTINCT to prevent counting duplicate rows caused by multiple LEFT JOINs
        $query->select('COUNT(DISTINCT b.id) AS nbcontract');
        $query->select('COUNT(DISTINCT CASE WHEN b.published = 1 THEN b.id END) AS nbactive');
        $query->select('COUNT(DISTINCT i.id) AS nbinvoices');
        
        // Select tag information
        $query->select($db->quoteName(['t.value', 't.color'], ['tag_value', 'tag_color']));
        $query->from($db->quoteName('#__ctr_client', 'a'));
        
        $query->join('LEFT', $db->quoteName('#__ctr_contract', 'b') . ' ON ' . $db->quoteName('b.client_id') . ' = ' . $db->quoteName('a.id'));
        $query->join('LEFT', $db->quoteName('#__ctr_invoices', 'i') . ' ON ' . $db->quoteName('i.client_id') . ' = ' . $db->quoteName('a.id'));
        // Join the tags table to get the name and color based on tag_id
        $query->join('LEFT', $db->quoteName('#__ctr_tags', 't') . ' ON ' . $db->quoteName('t.id') . ' = ' . $db->quoteName('a.tag_id'));
        $query->group($db->quoteName('a.id'));

        // Filter by published status (active / inactive)
        $published = $this->getState('filter.published');
        if ($published !== null && $published !== '') {
            $query->where($db->quoteName('a.published') . ' = ' . (int) $published);
        }

        // Filter by tag_id
        $tagId = $this->getState('filter.tag_id');
        if (is_numeric($tagId)) {
            $query->where($db->quoteName('a.tag_id') . ' = ' . (int) $tagId);
        }

        // Filter by search
        $search = $this->getState('filter.search');
        if (!empty($search)) {
            $search = $db->quote('%' . str_replace(' ', '%', $db->escape(trim($search), true) . '%'));
            $query->where('(' . $db->quoteName('a.client_name') . ' LIKE ' . $search . ' OR ' . $db->quoteName('a.company_name') . ' LIKE ' . $search . ')');
        }

        // Handling ordering (processed automatically by ListModel)
        $orderCol  = $this->state->get('list.ordering', 'a.client_name');
        $orderDirn = $this->state->get('list.direction', 'ASC');
        $query->order($db->escape($orderCol) . ' ' . $db->escape($orderDirn));

        return $query;
    }
}