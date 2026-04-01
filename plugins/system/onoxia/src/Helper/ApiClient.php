<?php
/**
 * ONOXIA API Client for Joomla
 *
 * @copyright 2026 OCENOX LTD
 * @license   GPL-2.0-or-later
 */

namespace Ocenox\Plugin\System\Onoxia\Helper;

defined('_JEXEC') or die;

use Joomla\Http\HttpFactory;

class ApiClient
{
    private const BASE_URL = 'https://onoxia.nz/api/v1/bot';

    private string $token;
    private int $lastStatus = 0;
    private string $lastError = '';

    public function __construct(string $token)
    {
        $this->token = $token;
    }

    public function getSite(): ?array
    {
        return $this->request('GET', '/site');
    }

    /**
     * Get the last HTTP status code (0 if no request was made).
     */
    public function getLastStatus(): int
    {
        return $this->lastStatus;
    }

    /**
     * Get the last error message (empty if no error).
     */
    public function getLastError(): string
    {
        return $this->lastError;
    }

    public function syncRag(array $sources, bool $deleteMissing = false): ?array
    {
        return $this->request('POST', '/rag/sync', [
            'sources'        => $sources,
            'delete_missing' => $deleteMissing,
        ]);
    }

    public function ingestLlms(string $url): ?array
    {
        return $this->request('POST', '/ingest/llms-txt', ['url' => $url]);
    }

    public function ingestSitemap(string $url): ?array
    {
        return $this->request('POST', '/ingest/sitemap', ['url' => $url]);
    }

    private function request(string $method, string $endpoint, ?array $body = null): ?array
    {
        $this->lastStatus = 0;
        $this->lastError  = '';

        if (empty($this->token)) {
            $this->lastError = 'No API token configured';
            return null;
        }

        try {
            $options = new \Joomla\Registry\Registry(['timeout' => 5]);
            $http    = (new HttpFactory())->getHttp($options);
            $url     = self::BASE_URL . $endpoint;
            $headers = [
                'Authorization' => 'Bearer ' . $this->token,
                'Accept'        => 'application/json',
                'Content-Type'  => 'application/json',
            ];

            if ($method === 'POST' && $body !== null) {
                $response = $http->post($url, json_encode($body), $headers);
            } else {
                $response = $http->get($url, $headers);
            }

            $this->lastStatus = $response->code;

            if ($response->code >= 400) {
                $decoded = json_decode($response->body, true);
                $this->lastError = $decoded['message'] ?? "HTTP {$response->code}";
                return null;
            }

            return json_decode($response->body, true);
        } catch (\Throwable $e) {
            $this->lastError = $e->getMessage();
            return null;
        }
    }
}
