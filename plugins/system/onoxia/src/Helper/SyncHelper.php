<?php
/**
 * ONOXIA RAG Sync Helper for Joomla
 *
 * @copyright 2026 OCENOX LTD
 * @license   GPL-2.0-or-later
 */

namespace Ocenox\Plugin\System\Onoxia\Helper;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Router\Route;

class SyncHelper
{
    private ApiClient $api;

    public function __construct(string $token)
    {
        $this->api = new ApiClient($token);
    }

    /**
     * Sync a Joomla article as RAG source.
     */
    public function syncArticle(object $article): void
    {
        $content = strip_tags($article->introtext . ' ' . ($article->fulltext ?? ''));
        if (empty(trim($content))) {
            return;
        }

        // Get category name for context tag
        $categoryName = null;
        if (!empty($article->catid)) {
            try {
                $db    = Factory::getContainer()->get('DatabaseDriver');
                $query = $db->getQuery(true)
                    ->select('title')
                    ->from('#__categories')
                    ->where('id = ' . (int) $article->catid);
                $categoryName = $db->setQuery($query)->loadResult();
            } catch (\Throwable $e) {
                // Ignore
            }
        }

        $tags = [];
        if ($categoryName) {
            $tags[] = mb_strtolower($categoryName);
        }

        $this->api->syncRag([
            [
                'name'         => $article->title,
                'type'         => 'text',
                'answer'       => "# {$article->title}\n\n{$content}",
                'context_tags' => !empty($tags) ? $tags : null,
            ],
        ], false);
    }

    /**
     * Delete a RAG source when article is trashed/deleted.
     */
    public function deleteArticle(object $article): void
    {
        // Will be cleaned up on next full sync (delete_missing)
    }

    /**
     * Sync ALL published articles as RAG sources (batch, with delete_missing).
     */
    public function syncAllArticles(): array
    {
        $db = Factory::getContainer()->get('DatabaseDriver');

        // Load all published articles with categories
        $query = $db->getQuery(true)
            ->select('a.id, a.title, a.introtext, a.fulltext, a.catid, c.title AS category_title')
            ->from('#__content AS a')
            ->leftJoin('#__categories AS c ON c.id = a.catid')
            ->where('a.state = 1');
        $articles = $db->setQuery($query)->loadObjectList();

        if (empty($articles)) {
            return ['success' => true, 'count' => 0];
        }

        $sources = [];
        foreach ($articles as $article) {
            $content = strip_tags($article->introtext . ' ' . ($article->fulltext ?? ''));
            if (empty(trim($content))) {
                continue;
            }

            $tags = [];
            if (!empty($article->category_title)) {
                $tags[] = mb_strtolower($article->category_title);
            }

            $sources[] = [
                'name'         => $article->title,
                'type'         => 'text',
                'answer'       => "# {$article->title}\n\n{$content}",
                'context_tags' => !empty($tags) ? $tags : null,
            ];
        }

        if (empty($sources)) {
            return ['success' => true, 'count' => 0];
        }

        $result = $this->api->syncRag($sources, true);

        return [
            'success' => $result !== null,
            'count'   => count($sources),
            'error'   => $this->api->getLastError(),
        ];
    }

    /**
     * Import llms.txt (tries llms-full.txt first, then llms.txt).
     */
    public function importLlmsTxt(string $siteUrl): array
    {
        $siteUrl = rtrim($siteUrl, '/');

        // Try llms-full.txt first (richer content)
        $result = $this->api->ingestLlms($siteUrl . '/llms-full.txt');
        if ($result !== null) {
            return ['success' => true, 'url' => $siteUrl . '/llms-full.txt'];
        }

        // Fallback to llms.txt
        $result = $this->api->ingestLlms($siteUrl . '/llms.txt');

        return [
            'success' => $result !== null,
            'url'     => $siteUrl . '/llms.txt',
            'error'   => $this->api->getLastError(),
        ];
    }

    /**
     * Import sitemap.xml.
     */
    public function importSitemap(string $siteUrl): array
    {
        $siteUrl = rtrim($siteUrl, '/');
        $result = $this->api->ingestSitemap($siteUrl . '/sitemap.xml');

        return [
            'success' => $result !== null,
            'url'     => $siteUrl . '/sitemap.xml',
            'error'   => $this->api->getLastError(),
        ];
    }

    public function getApiClient(): ApiClient
    {
        return $this->api;
    }
}
