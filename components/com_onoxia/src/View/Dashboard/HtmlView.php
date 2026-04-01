<?php
/**
 * @copyright 2026 OCENOX LTD
 * @license   GPL-2.0-or-later
 */

namespace Ocenox\Component\Onoxia\Administrator\View\Dashboard;

defined('_JEXEC') or die;

use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\CMS\Toolbar\ToolbarHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Factory;
use Joomla\CMS\Uri\Uri;
use Joomla\Registry\Registry;
use Ocenox\Plugin\System\Onoxia\Helper\ApiClient;

class HtmlView extends BaseHtmlView
{
    public bool $pluginEnabled = false;
    public int $pluginId = 0;
    public bool $tokenSet = false;
    public string $tokenPreview = '';
    public bool $apiConnected = false;
    public string $apiError = '';
    public ?array $siteInfo = null;
    public bool $syncArticles = false;
    public bool $syncLlms = false;
    public bool $syncSitemap = false;
    public $enabledMenuitems = [];
    public string $cronSecret = '';
    public string $cronUrl = '';
    public bool $demoMode = false;

    public function display($tpl = null): void
    {
        ToolbarHelper::title(Text::_('COM_ONOXIA'), 'fas fa-robot');

        $this->demoMode = defined('ONOXIA_DEMO') && ONOXIA_DEMO;
        $this->checkStatus();

        parent::display($tpl);
    }

    private function checkStatus(): void
    {
        // Check if plugin is installed and enabled
        $this->pluginEnabled = PluginHelper::isEnabled('system', 'onoxia');

        // Get plugin extension_id for settings link
        $db = Factory::getContainer()->get('DatabaseDriver');
        $query = $db->getQuery(true)
            ->select('extension_id, params')
            ->from('#__extensions')
            ->where($db->quoteName('element') . ' = ' . $db->quote('onoxia'))
            ->where($db->quoteName('type') . ' = ' . $db->quote('plugin'))
            ->where($db->quoteName('folder') . ' = ' . $db->quote('system'));
        $plugin = $db->setQuery($query)->loadObject();

        if (!$plugin) {
            return;
        }

        $this->pluginId = (int) $plugin->extension_id;
        $params = new Registry($plugin->params);

        // Token status
        $token = $params->get('api_token', '');
        $this->tokenSet = !empty($token);
        if ($this->tokenSet) {
            $this->tokenPreview = $this->demoMode ? '••••••••••••' : substr($token, 0, 8) . '...';
        }

        // Sync settings
        $this->syncArticles = (bool) $params->get('sync_articles', 0);
        $this->syncLlms = (bool) $params->get('sync_llms_txt', 0);
        $this->syncSitemap = (bool) $params->get('sync_sitemap', 0);
        $this->enabledMenuitems = (array) $params->get('enabled_menuitems', []);

        // Cron secret (auto-generate if missing)
        $this->cronSecret = $params->get('onoxia_cron_secret', '');
        if (empty($this->cronSecret)) {
            $this->cronSecret = bin2hex(random_bytes(16));
            // Save to plugin params
            $paramsObj = json_decode($plugin->params ?? '{}', true) ?: [];
            $paramsObj['onoxia_cron_secret'] = $this->cronSecret;
            $db->setQuery(
                $db->getQuery(true)
                    ->update('#__extensions')
                    ->set($db->quoteName('params') . ' = ' . $db->quote(json_encode($paramsObj)))
                    ->where($db->quoteName('extension_id') . ' = ' . (int) $plugin->extension_id)
            )->execute();
        }
        $this->cronUrl = $this->demoMode
            ? Uri::root() . 'administrator/index.php?option=com_onoxia&task=sync.cron&secret=••••••••'
            : Uri::root() . 'administrator/index.php?option=com_onoxia&task=sync.cron&secret=' . $this->cronSecret;

        // API connection test
        if ($this->tokenSet) {
            try {
                $api = new ApiClient($token);
                $site = $api->getSite();
                if ($site && isset($site['id'])) {
                    $this->apiConnected = true;
                    $this->siteInfo = $site;
                } else {
                    $status = $api->getLastStatus();
                    $error  = $api->getLastError();
                    if ($status === 403) {
                        $this->apiError = Text::_('COM_ONOXIA_ERROR_PERMISSION') . ': ' . $error;
                    } elseif ($status === 401) {
                        $this->apiError = Text::_('COM_ONOXIA_ERROR_UNAUTHORIZED');
                    } elseif ($error) {
                        $this->apiError = $error;
                    } else {
                        $this->apiError = Text::_('COM_ONOXIA_ERROR_INVALID_RESPONSE');
                    }
                }
            } catch (\Throwable $e) {
                $this->apiError = $e->getMessage();
            }
        }
    }
}
