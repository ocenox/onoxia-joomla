<?php
/**
 * @copyright 2026 OCENOX LTD
 * @license   GPL-2.0-or-later
 */

defined('_JEXEC') or die;

use Joomla\CMS\Dispatcher\ComponentDispatcherFactoryInterface;
use Joomla\CMS\Extension\ComponentInterface;
use Joomla\CMS\Extension\Service\Provider\ComponentDispatcherFactory;
use Joomla\CMS\Extension\Service\Provider\MVCFactory;
use Joomla\CMS\MVC\Factory\MVCFactoryInterface;
use Joomla\DI\Container;
use Joomla\DI\ServiceProviderInterface;
use Ocenox\Component\Onoxia\Administrator\Extension\OnoxiaComponent;

return new class implements ServiceProviderInterface {
    public function register(Container $container): void
    {
        $container->registerServiceProvider(new MVCFactory('\\Ocenox\\Component\\Onoxia'));
        $container->registerServiceProvider(new ComponentDispatcherFactory('\\Ocenox\\Component\\Onoxia'));

        $container->set(
            ComponentInterface::class,
            function (Container $container) {
                $component = new OnoxiaComponent($container->get(ComponentDispatcherFactoryInterface::class));
                $component->setMVCFactory($container->get(MVCFactoryInterface::class));
                return $component;
            }
        );
    }
};
