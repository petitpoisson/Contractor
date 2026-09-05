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

\defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use XavierSpirlet\Module\ContractorClient\Site\Dispatcher\Dispatcher;

$app = Factory::getApplication();

// Directly instantiate the modern namespaced dispatcher to prevent legacy fallback infinite loops
$dispatcher = new Dispatcher($module, $app, clone $app->getInput());
$dispatcher->dispatch();