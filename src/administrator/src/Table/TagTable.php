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

class TagTable extends Table
{
    public function __construct(DatabaseDriver $db)
    {
        parent::__construct('#__ctr_tags', 'id', $db);
    }

    /**
     * Method to compute and validate the data before saving.
     *
     * @return  boolean  True if ok, Exception on error.
     */
    public function check()
    {
        // Empty for now
        return parent::check();
    }
}