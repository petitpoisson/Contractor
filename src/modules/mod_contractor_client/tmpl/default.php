<?php
/**
 * @package     Joomla.Site
 * @subpackage  mod_contractor_client
 *
 * @author      Xavier Spirlet (Petitpoisson) <https://www.petitpoisson.be>
 * @copyright   Copyright (C) 2026 Xavier Spirlet. All rights reserved.
 * @license     GNU General Public License version 3 or later; see LICENSE.txt
 * @link        https://www.petitpoisson.be
 */

// No direct access
\defined('_JEXEC') or die;

// Acts as a router based on component configuration
if ($config['font_framework'] === '1') {
    require __DIR__ . '/default_uikit.php';
} else {
    require __DIR__ . '/default_bootstrap.php';
}