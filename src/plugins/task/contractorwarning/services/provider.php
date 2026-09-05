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

\defined('_JEXEC') or die;

use Joomla\CMS\Extension\PluginInterface;
use Joomla\CMS\Factory;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\Database\DatabaseInterface;
use Joomla\DI\Container;
use Joomla\DI\ServiceProviderInterface;
use XavierSpirlet\Plugin\Task\ContractorWarning\Extension\ContractorWarning;

return new class () implements ServiceProviderInterface {
    /**
     * Registers the service provider with a DI container.
     */
    public function register(Container $container)
    {
        $container->set(
            PluginInterface::class,
            $container->lazy(ContractorWarning::class, function (Container $container) {
                $plugin = new ContractorWarning(
                    (array) PluginHelper::getPlugin('task', 'contractorwarning')
                );
                $plugin->setApplication(Factory::getApplication());
                $plugin->setDatabase($container->get(DatabaseInterface::class));

                return $plugin;
            })
        );
    }
};