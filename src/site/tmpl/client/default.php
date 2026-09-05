<?php
/**
 * @package     Joomla.Site
 * @subpackage  com_contractor
 *
 * @author      Xavier Spirlet (Petitpoisson) <https://www.petitpoisson.be>
 * @copyright   Copyright (C) 2026 Xavier Spirlet. All rights reserved.
 * @license     GNU General Public License version 3 or later; see LICENSE.txt
 * @link        https://www.petitpoisson.be
 */

// No direct access
\defined('_JEXEC') or die;

// Acts as a router based on component configuration
if ($this->config['font_framework'] === '1') {
    echo $this->loadTemplate('uikit');
} else {
    echo $this->loadTemplate('bootstrap');
}