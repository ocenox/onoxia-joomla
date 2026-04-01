<?php
/**
 * @copyright 2026 OCENOX LTD
 * @license   GPL-2.0-or-later
 */

namespace Ocenox\Component\Onoxia\Administrator\Controller;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Controller\BaseController;
use Joomla\CMS\Session\Session;
use Joomla\CMS\Uri\Uri;
use Joomla\Registry\Registry;
use Ocenox\Plugin\System\Onoxia\Helper\SyncHelper;

class SyncController extends BaseController
{
    private function isDemoMode(): bool
    {
        return defined('ONOXIA_DEMO') && ONOXIA_DEMO;
    }

    /**
     * AJAX: Sync all published articles.
     */
    public function articles(): void
    {
        $this->checkAjaxToken();
        if ($this->isDemoMode()) {
            $this->jsonResponse(true, 'Demo mode — sync disabled');
            return;
        }
        $params = $this->getPluginParams();
        $token = $params->get('api_token', '');

        if (empty($token)) {
            $this->jsonResponse(false, 'API token not configured');
            return;
        }

        if (!$params->get('sync_articles', 0)) {
            $this->jsonResponse(true, 'skipped');
            return;
        }

        $sync = new SyncHelper($token);
        $result = $sync->syncAllArticles();

        $this->jsonResponse(
            $result['success'],
            $result['success']
                ? ($result['count'] . ' articles synced')
                : ($result['error'] ?: 'Sync failed')
        );
    }

    /**
     * AJAX: Import llms.txt / llms-full.txt.
     */
    public function llms(): void
    {
        $this->checkAjaxToken();
        if ($this->isDemoMode()) {
            $this->jsonResponse(true, 'Demo mode — sync disabled');
            return;
        }
        $params = $this->getPluginParams();
        $token = $params->get('api_token', '');

        if (empty($token)) {
            $this->jsonResponse(false, 'API token not configured');
            return;
        }

        if (!$params->get('sync_llms_txt', 0)) {
            $this->jsonResponse(true, 'skipped');
            return;
        }

        $sync = new SyncHelper($token);
        $result = $sync->importLlmsTxt(Uri::root());

        $this->jsonResponse(
            $result['success'],
            $result['success']
                ? ('Import triggered: ' . basename($result['url']) . ' (processing on server)')
                : ($result['error'] ?: 'Import failed')
        );
    }

    /**
     * AJAX: Import sitemap.xml.
     */
    public function sitemap(): void
    {
        $this->checkAjaxToken();
        if ($this->isDemoMode()) {
            $this->jsonResponse(true, 'Demo mode — sync disabled');
            return;
        }
        $params = $this->getPluginParams();
        $token = $params->get('api_token', '');

        if (empty($token)) {
            $this->jsonResponse(false, 'API token not configured');
            return;
        }

        if (!$params->get('sync_sitemap', 0)) {
            $this->jsonResponse(true, 'skipped');
            return;
        }

        $sync = new SyncHelper($token);
        $result = $sync->importSitemap(Uri::root());

        $this->jsonResponse(
            $result['success'],
            $result['success']
                ? 'Import triggered: sitemap.xml (processing on server)'
                : ($result['error'] ?: 'Import failed')
        );
    }

    /**
     * Cron endpoint: Run all enabled syncs.
     * Called via: index.php?option=com_onoxia&task=sync.cron&secret=KEY
     */
    public function cron(): void
    {
        $params = $this->getPluginParams();
        $secret = $params->get('onoxia_cron_secret', '');
        $input = $this->app->getInput()->getString('secret', '');

        if (empty($secret) || $input !== $secret) {
            header('HTTP/1.1 403 Forbidden');
            echo 'Invalid secret';
            $this->app->close();
            return;
        }

        $token = $params->get('api_token', '');
        if (empty($token)) {
            echo "ERROR: No API token configured\n";
            $this->app->close();
            return;
        }

        $sync = new SyncHelper($token);
        $siteUrl = Uri::root();
        $output = [];

        // Articles
        if ($params->get('sync_articles', 0)) {
            $result = $sync->syncAllArticles();
            $output[] = $result['success']
                ? "OK: {$result['count']} articles synced"
                : "FAIL: articles — " . ($result['error'] ?: 'unknown error');
        }

        // llms.txt
        if ($params->get('sync_llms_txt', 0)) {
            $result = $sync->importLlmsTxt($siteUrl);
            $output[] = $result['success']
                ? "OK: " . basename($result['url']) . " import triggered"
                : "FAIL: llms.txt — " . ($result['error'] ?: 'unknown error');
        }

        // Sitemap
        if ($params->get('sync_sitemap', 0)) {
            $result = $sync->importSitemap($siteUrl);
            $output[] = $result['success']
                ? "OK: sitemap import triggered"
                : "FAIL: sitemap — " . ($result['error'] ?: 'unknown error');
        }

        if (empty($output)) {
            $output[] = "No sync options enabled.";
        }

        header('Content-Type: text/plain; charset=utf-8');
        echo implode("\n", $output) . "\n";
        $this->app->close();
    }

    private function getPluginParams(): Registry
    {
        $db = Factory::getContainer()->get('DatabaseDriver');
        $query = $db->getQuery(true)
            ->select('params')
            ->from('#__extensions')
            ->where($db->quoteName('element') . ' = ' . $db->quote('onoxia'))
            ->where($db->quoteName('type') . ' = ' . $db->quote('plugin'))
            ->where($db->quoteName('folder') . ' = ' . $db->quote('system'));
        $raw = $db->setQuery($query)->loadResult();

        return new Registry($raw ?: '{}');
    }

    private function checkAjaxToken(): void
    {
        if (!Session::checkToken('get') && !Session::checkToken('post') && !Session::checkToken()) {
            $this->jsonResponse(false, 'Invalid CSRF token');
            $this->app->close();
        }
    }

    private function jsonResponse(bool $success, string $message): void
    {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['success' => $success, 'message' => $message]);
        $this->app->close();
    }
}
