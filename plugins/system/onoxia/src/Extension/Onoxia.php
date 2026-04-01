<?php
/**
 * ONOXIA System Plugin
 *
 * Injects the ONOXIA chat widget and syncs content as RAG sources.
 *
 * @copyright 2026 OCENOX LTD
 * @license   GPL-2.0-or-later
 */

namespace Ocenox\Plugin\System\Onoxia\Extension;

defined('_JEXEC') or die;

use Joomla\CMS\Event\Model\AfterDeleteEvent;
use Joomla\CMS\Event\Model\AfterSaveEvent;
use Joomla\CMS\Event\Model\PrepareFormEvent;
use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\Event\SubscriberInterface;
use Ocenox\Plugin\System\Onoxia\Helper\ApiClient;
use Ocenox\Plugin\System\Onoxia\Helper\SyncHelper;

class Onoxia extends CMSPlugin implements SubscriberInterface
{
    protected $autoloadLanguage = true;

    public static function getSubscribedEvents(): array
    {
        return [
            'onAfterRender'         => 'onAfterRender',
            'onContentAfterSave'    => 'onContentAfterSave',
            'onContentAfterDelete'  => 'onContentAfterDelete',
            'onContentPrepareForm'  => 'onContentPrepareForm',
        ];
    }

    /**
     * Inject widget script before </body>.
     */
    public function onAfterRender(): void
    {
        $app = $this->getApplication();

        // Only frontend
        if (!$app->isClient('site')) {
            return;
        }

        $token = $this->params->get('api_token', '');
        if (empty($token)) {
            return;
        }

        // Menu item restriction
        $allowed = $this->params->get('enabled_menuitems', []);
        if (!empty($allowed)) {
            $activeMenu = $app->getMenu()->getActive();
            if ($activeMenu && !in_array($activeMenu->id, (array) $allowed)) {
                return;
            }
        }

        // Get site info (cached)
        $siteInfo = $this->getSiteInfo($token);
        $siteUuid = $siteInfo['uuid'] ?? '';
        $widgetUrl = $siteInfo['widget_url'] ?? 'https://onoxia.nz/widget.js';
        if (empty($siteUuid)) {
            return;
        }

        // Build script tag
        $attrs = 'data-site="' . htmlspecialchars($siteUuid, ENT_QUOTES) . '"';

        $tags = $this->params->get('context_tags', '');
        if (!empty($tags)) {
            $attrs .= ' data-tags="' . htmlspecialchars($tags, ENT_QUOTES) . '"';
        }

        // Build context (see onoxiaSetContext API docs)
        $context = [];

        // Logged-in user data
        $user = $app->getIdentity();
        if ($user && !$user->guest) {
            $context['name']        = $user->name;
            $context['email']       = $user->email;
            $context['customer_id'] = (string) $user->id;

            // User group names as role
            $groups = $user->getAuthorisedGroups();
            if (!empty($groups)) {
                try {
                    $db = \Joomla\CMS\Factory::getContainer()->get('DatabaseDriver');
                    $query = $db->getQuery(true)
                        ->select('title')
                        ->from('#__usergroups')
                        ->whereIn('id', $groups);
                    $groupNames = $db->setQuery($query)->loadColumn();
                    if (!empty($groupNames)) {
                        $context['role'] = implode(', ', $groupNames);
                    }
                } catch (\Throwable $e) {
                    // Ignore
                }
            }
        }

        // Current page path
        $context['page'] = \Joomla\CMS\Uri\Uri::getInstance()->getPath();

        // Joomla locale
        $context['locale'] = $app->getLanguage()->getTag();

        // Current menu item alias
        $activeMenu = $app->getMenu()->getActive();
        if ($activeMenu) {
            $context['menu_alias'] = $activeMenu->alias;
        }

        if (!empty($context)) {
            $attrs .= " data-context='" . htmlspecialchars(json_encode($context), ENT_QUOTES) . "'";
        }

        $script = '<script src="' . htmlspecialchars($widgetUrl, ENT_QUOTES) . '" ' . $attrs . '></script>';

        // Inject before </body>
        $body = $app->getBody();
        $body = str_ireplace('</body>', $script . "\n</body>", $body);
        $app->setBody($body);
    }

    /**
     * Mask sensitive fields in plugin settings form when in demo mode.
     */
    public function onContentPrepareForm(PrepareFormEvent $event): void
    {
        if (!(defined('ONOXIA_DEMO') && ONOXIA_DEMO)) {
            return;
        }

        $form = $event->getForm();
        $data = $event->getData();

        if ($form->getName() !== 'com_plugins.plugin') {
            return;
        }

        $element = is_array($data) ? ($data['element'] ?? '') : ($data->element ?? '');
        $folder  = is_array($data) ? ($data['folder'] ?? '') : ($data->folder ?? '');

        if ($element !== 'onoxia' || $folder !== 'system') {
            return;
        }

        $form->setFieldAttribute('api_token', 'type', 'password', 'params');
        $form->setFieldAttribute('api_token', 'readonly', 'true', 'params');
    }

    /**
     * Sync article content as RAG source when saved.
     */
    public function onContentAfterSave(AfterSaveEvent $event): void
    {
        if (!$this->params->get('sync_articles', 0)) {
            return;
        }

        $context = $event->getContext();
        $article = $event->getItem();

        if ($context !== 'com_content.article' && $context !== 'com_content.form') {
            return;
        }

        if ($article->state != 1) {
            return;
        }

        $sync = new SyncHelper($this->params->get('api_token', ''));
        $sync->syncArticle($article);
    }

    /**
     * Remove RAG source when article is deleted.
     */
    public function onContentAfterDelete(AfterDeleteEvent $event): void
    {
        if (!$this->params->get('sync_articles', 0)) {
            return;
        }

        $context = $event->getContext();
        $article = $event->getItem();

        if ($context !== 'com_content.article') {
            return;
        }

        $sync = new SyncHelper($this->params->get('api_token', ''));
        $sync->deleteArticle($article);
    }

    /**
     * Get and cache site info (UUID + widget URL) from API.
     * Cached in plugin params for 24h to avoid API calls on every page load.
     */
    private function getSiteInfo(string $token): array
    {
        $cachedUuid = $this->params->get('_cached_site_uuid', '');
        $cachedWidgetUrl = $this->params->get('_cached_widget_url', '');
        $cachedAt = (int) $this->params->get('_cached_site_uuid_at', 0);

        if (!empty($cachedUuid) && (time() - $cachedAt) < 86400) {
            return ['uuid' => $cachedUuid, 'widget_url' => $cachedWidgetUrl ?: 'https://onoxia.nz/widget.js'];
        }

        try {
            $api  = new ApiClient($token);
            $site = $api->getSite();
            $uuid = $site['id'] ?? '';
            $widgetUrl = $site['widget_url'] ?? 'https://onoxia.nz/widget.js';
        } catch (\Throwable $e) {
            return ['uuid' => $cachedUuid, 'widget_url' => $cachedWidgetUrl ?: 'https://onoxia.nz/widget.js'];
        }

        if (!empty($uuid)) {
            $db = \Joomla\CMS\Factory::getContainer()->get('DatabaseDriver');
            $params = json_decode($db->setQuery(
                $db->getQuery(true)
                    ->select('params')
                    ->from('#__extensions')
                    ->where($db->quoteName('element') . ' = ' . $db->quote('onoxia'))
                    ->where($db->quoteName('type') . ' = ' . $db->quote('plugin'))
                    ->where($db->quoteName('folder') . ' = ' . $db->quote('system'))
            )->loadResult() ?: '{}', true);

            $params['_cached_site_uuid'] = $uuid;
            $params['_cached_widget_url'] = $widgetUrl;
            $params['_cached_site_uuid_at'] = time();

            $db->setQuery(
                $db->getQuery(true)
                    ->update('#__extensions')
                    ->set($db->quoteName('params') . ' = ' . $db->quote(json_encode($params)))
                    ->where($db->quoteName('element') . ' = ' . $db->quote('onoxia'))
                    ->where($db->quoteName('type') . ' = ' . $db->quote('plugin'))
                    ->where($db->quoteName('folder') . ' = ' . $db->quote('system'))
            )->execute();
        }

        return ['uuid' => $uuid, 'widget_url' => $widgetUrl];
    }
}
