<?php
/**
 * ONOXIA System Plugin - Service Provider
 *
 * @copyright 2026 OCENOX LTD
 * @license   GPL-2.0-or-later
 */

defined('_JEXEC') or die;

use Joomla\CMS\Extension\PluginInterface;
use Joomla\CMS\Factory;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\DI\Container;
use Joomla\DI\ServiceProviderInterface;
use Joomla\Event\DispatcherInterface;
use Ocenox\Plugin\System\Onoxia\Extension\Onoxia;

return new class implements ServiceProviderInterface {
    public function register(Container $container): void
    {
        $container->set(
            PluginInterface::class,
            function (Container $container) {
                $dispatcher = $container->get(DispatcherInterface::class);
                $plugin     = new Onoxia($dispatcher, (array) PluginHelper::getPlugin('system', 'onoxia'));
                $plugin->setApplication(Factory::getApplication());

                return $plugin;
            }
        );
    }
};
