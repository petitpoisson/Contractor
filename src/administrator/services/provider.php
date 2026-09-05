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

// No direct access
\defined('_JEXEC') or die;

/**
 * Service provider for Contractor component.
 */

use Joomla\CMS\Dispatcher\ComponentDispatcherFactoryInterface;
use Joomla\CMS\Extension\ComponentInterface;
use Joomla\CMS\Extension\Service\Provider\ComponentDispatcherFactory;
use Joomla\CMS\Extension\Service\Provider\MVCFactory;
use Joomla\CMS\MVC\Factory\MVCFactoryInterface;
use Joomla\DI\Container;
use Joomla\DI\ServiceProviderInterface;
use XavierSpirlet\Component\Contractor\Administrator\Extension\ContractorComponent;

return new class implements ServiceProviderInterface
{
    public function register(Container $container): void
    {
        $container->registerServiceProvider(new MVCFactory('\\XavierSpirlet\\Component\\Contractor'));
        $container->registerServiceProvider(new ComponentDispatcherFactory('\\XavierSpirlet\\Component\\Contractor'));

        $container->set(
            ComponentInterface::class,
            function (Container $container) {
                $component = new ContractorComponent($container->get(ComponentDispatcherFactoryInterface::class));
                
                // Changed from setRegistry to setMVCFactory for Joomla 5/6 compatibility
                $component->setMVCFactory($container->get(MVCFactoryInterface::class));

                return $component;
            }
        );
    }
};