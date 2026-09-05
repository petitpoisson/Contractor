<?php
/**
 * @package     Joomla.Plugin
 * @subpackage  Quickicon.Contractorshortcut
 *
 * @copyright   (C) 2026 Petitpoisson. <https://www.petitpoisson.be>
 * @license     GNU General Public License version 3 or later
 */

\defined('_JEXEC') or die;

use Joomla\CMS\Extension\PluginInterface;
use Joomla\CMS\Factory;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\DI\Container;
use Joomla\DI\ServiceProviderInterface;
use Joomla\Event\DispatcherInterface;
// IMPORT CORRIGÉ
use XavierSpirlet\Plugin\Quickicon\Contractorshortcut\Extension\Contractorshortcut;

return new class () implements ServiceProviderInterface {
    /**
     * Registers the service provider with a DI container.
     */
    public function register(Container $container)
    {
        $container->set(
            PluginInterface::class,
            function (Container $container) {
                // INSTANCIATION CORRIGÉE
                $plugin = new Contractorshortcut(
                    $container->get(DispatcherInterface::class),
                    (array) PluginHelper::getPlugin('quickicon', 'contractorshortcut')
                );
                $plugin->setApplication(Factory::getApplication());

                return $plugin;
            }
        );
    }
};