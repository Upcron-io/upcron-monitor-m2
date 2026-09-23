<?php

declare(strict_types=1);

namespace Upcron\Monitor\Service;

use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\HTTP\Client\Curl;
use Psr\Log\LoggerInterface;
use Upcron\Monitor\Model\Config\ConfigProvider;

class UpcronApiClient
{
    public function __construct(
        private readonly ConfigProvider $configProvider,
        private readonly Curl $curl,
        private readonly LoggerInterface $logger,
    ) {
    }

    /**
     * Test connection by listing monitors.
     *
     * @throws LocalizedException
     */
    public function testConnection(): void
    {
        $url = rtrim($this->getValidatedBaseUrl(), '/') . '/projects';
        $this->request('GET', $url);
    }

    /**
     * Create a new heartbeat on Upcron.
     *
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     * @throws LocalizedException
     */
    public function createHeartbeat(array $data): array
    {
        $url = rtrim($this->getValidatedBaseUrl(), '/') . '/heartbeats';
        return $this->request('POST', $url, $data);
    }

    /**
     * Update an existing heartbeat on Upcron.
     *
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     * @throws LocalizedException
     */
    public function updateHeartbeat(string $uuid, array $data): array
    {
        $projectId = $this->configProvider->getProjectId();
        $url = rtrim($this->getValidatedBaseUrl(), '/') . '/heartbeats/' . $projectId . '/' . $uuid;
        unset($data['project_id']);
        return $this->request('PUT', $url, $data);
    }

    /**
     * Get current organization's subscription resource usage.
     *
     * @return array<string, mixed>
     * @throws LocalizedException
     */
    public function getSubscriptionUsage(): array
    {
        $url = rtrim($this->getValidatedBaseUrl(), '/') . '/subscription/usage';
        return $this->request('GET', $url);
    }

    /**
     * Ping a URL (start/success/fail) — never throws, errors are logged only.
     */
    public function ping(string $url): void
    {
        try {
            $this->request('GET', $url);
        } catch (\Throwable $e) {
            $status = $this->curl->getStatus();
            $logMessage = '[upcron_monitor] UpcronApiClient: ping failed | url=' . $url . ' | error=' . $e->getMessage();

            if ($status === 429) {
                $headers = $this->curl->getHeaders();
                $retryAfter = $headers['Retry-After'] ?? $headers['retry-after'] ?? null;
                if ($retryAfter !== null) {
                    $logMessage .= ' | retry_after=' . $retryAfter;
                }
            }

            $this->logger->error($logMessage);
        }
    }

    /**
     * @throws LocalizedException
     */
    private function getValidatedBaseUrl(): string
    {
        $baseUrl = $this->configProvider->getBaseUrl();

        if (!str_starts_with($baseUrl, 'https://')) {
            throw new LocalizedException(__('API Base URL must use HTTPS'));
        }

        return $baseUrl;
    }

    /**
     * @param array<string, mixed>|null $body
     * @return array<string, mixed>
     * @throws LocalizedException
     */
    private function request(string $method, string $url, ?array $body = null): array
    {
        $token = $this->configProvider->getBearerToken();

        $this->curl->setOption(CURLOPT_CONNECTTIMEOUT, $this->configProvider->getConnectTimeout());
        $this->curl->setOption(CURLOPT_TIMEOUT, $this->configProvider->getTransferTimeout());
        $this->curl->addHeader('Authorization', 'Bearer ' . $token);
        $this->curl->addHeader('Content-Type', 'application/json');
        $this->curl->addHeader('Accept', 'application/json');
        $this->curl->addHeader('User-Agent', 'upcron-magento2-monitor/1.0');

        try {
            if ($method === 'POST') {
                $this->curl->setOption(CURLOPT_CUSTOMREQUEST, 'POST');
                $this->curl->post($url, json_encode($body ?? [], JSON_THROW_ON_ERROR));
            } elseif ($method === 'PUT') {
                $json = json_encode($body ?? [], JSON_THROW_ON_ERROR);
                $this->curl->setOption(CURLOPT_CUSTOMREQUEST, 'PUT');
                $this->curl->post($url, $json);
            } else {
                $this->curl->setOption(CURLOPT_CUSTOMREQUEST, 'GET');
                $this->curl->get($url);
            }
        } catch (\Exception $e) {
            $this->logger->error(
                '[upcron_monitor] UpcronApiClient: connection error | url=' . $url . ' | error=' . $e->getMessage()
            );
            throw new LocalizedException(
                __('Upcron connection error: %1. Check your API Base URL in Stores → Configuration → Upcron Monitor.', $e->getMessage()),
                $e
            );
        }

        $status = $this->curl->getStatus();
        $responseBody = $this->curl->getBody();

        if ($status < 200 || $status >= 300) {
            $reason = $this->resolveReason($status, $responseBody);
            $this->logger->error(
                '[upcron_monitor] UpcronApiClient: API error | url=' . $url . ' | status=' . $status
            );
            throw new LocalizedException(
                __('Upcron API error: %1 %2. Check your Bearer token in Stores → Configuration → Upcron Monitor.', $status, $reason)
            );
        }

        if ($responseBody === '' || $responseBody === null) {
            return [];
        }

        try {
            return (array) json_decode($responseBody, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException) {
            return [];
        }
    }

    private function resolveReason(int $status, string $body): string
    {
        $map = [
            400 => 'Bad Request',
            401 => 'Unauthorized',
            403 => 'Forbidden',
            404 => 'Not Found',
            422 => 'Unprocessable Entity',
            429 => 'Too Many Requests',
            500 => 'Internal Server Error',
            502 => 'Bad Gateway',
            503 => 'Service Unavailable',
        ];

        if (isset($map[$status])) {
            return $map[$status];
        }

        // Try to extract message from JSON body
        try {
            $decoded = json_decode($body, true, 512, JSON_THROW_ON_ERROR);
            if (isset($decoded['message']) && is_string($decoded['message'])) {
                return $decoded['message'];
            }
        } catch (\JsonException) {
            // Ignore — use status code only
        }

        return (string) $status;
    }
}
