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

namespace XavierSpirlet\Component\Contractor\Administrator\View\Dashboard;

\defined('_JEXEC') or die;

use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use XavierSpirlet\Component\Contractor\Administrator\Helper\ContractorHelper;

class HtmlView extends BaseHtmlView
{
    public $stats;
    public $config;
    public $logs;

    /**
     * Execute and display a template script.
     *
     * @param   string  $tpl  The name of the template file to parse.
     * @return  mixed         A string if successful, otherwise an Error object.
     */
    public function display($tpl = null)
    {
        $this->config = ContractorHelper::getConfig();
        
        // Retrieve the warn_days from config, default to 30 if not set
        $warnDays = (int) ($this->config['warn_days'] ?? 30);
        
        $model = $this->getModel();

        $this->stats = $model->getStats($warnDays);
        $this->logs  = $model->getLogs(20);

        return parent::display($tpl);
    }
}