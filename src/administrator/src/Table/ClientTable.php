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

class ClientTable extends Table
{
    public function __construct(DatabaseDriver $db)
    {
        parent::__construct('#__ctr_client', 'id', $db);
    }

    /**
     * Method to compute and validate the data before saving.
     *
     * @return  boolean  True if ok, Exception on error.
     */
    public function check()
    {
        // Convert empty string or 0 to null for integer foreign keys
        // This prevents MySQL strict mode errors (Incorrect integer value: '')
        if (empty($this->joomla_user_id)) {
            $this->joomla_user_id = null;
        }
        // Apply the exact same logic for the newly added tag_id
        if (empty($this->tag_id)) {
            $this->tag_id = null;
        }

        return parent::check();
    }
}