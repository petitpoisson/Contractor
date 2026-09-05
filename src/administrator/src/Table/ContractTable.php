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

namespace XavierSpirlet\Component\Contractor\Administrator\Table;

\defined('_JEXEC') or die;

use Joomla\CMS\Table\Table;
use Joomla\Database\DatabaseDriver;

/**
 * Contract Table class
 * Maps to the #__ctr_contract database table.
 */
class ContractTable extends Table
{
    /**
     * Constructor
     *
     * @param   DatabaseDriver  $db  Database connector object
     */
    public function __construct(DatabaseDriver $db)
    {
        // Table name, primary key, and database driver
        parent::__construct('#__ctr_contract', 'id', $db);
    }

    /**
     * Overloaded check method to ensure data integrity before saving.
     * This runs automatically right before Joomla executes the INSERT/UPDATE query.
     *
     * @return  boolean  True on success.
     */
    public function check()
    {
        // If tag_id is an empty string (no tag selected), force it to actual NULL
        // to prevent MySQL strict mode from crashing on an INT column.
        if (empty($this->tag_id)) {
            $this->tag_id = null;
        }

        // Same protection for the lastmsg date field (which is also DEFAULT NULL)
        if (empty($this->lastmsg) || $this->lastmsg === '0000-00-00') {
            $this->lastmsg = null;
        }

        // Ensure title is not entirely empty
        if (trim($this->name) === '') {
            $this->setError(\Joomla\CMS\Language\Text::_('COM_CONTRACTOR_CONTRACT_TABLETITLE'));
            return false;
        }

        return parent::check();
    }
}