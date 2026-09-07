<?php

declare(strict_types=1);

namespace Upcron\Monitor\Model\Config;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Encryption\EncryptorInterface;

class ConfigProvider
{
    public const XML_PATH_BASE_URL = 'upcron_monitor/api/base_url';
    public const XML_PATH_BEARER_TOKEN = 'upcron_monitor/api/bearer_token';
    public const XML_PATH_PROJECT_ID = 'upcron_monitor/api/project_id';
    public const XML_PATH_CONNECT_TIMEOUT = 'upcron_monitor/api/connect_timeout';
    public const XML_PATH_TRANSFER_TIMEOUT = 'upcron_monitor/api/transfer_timeout';

    public function __construct(
        private readonly ScopeConfigInterface $scopeConfig,
        private readonly EncryptorInterface $encryptor,
    ) {
    }

    public function getBaseUrl(): string
    {
        return (string) $this->scopeConfig->getValue(self::XML_PATH_BASE_URL);
    }

    public function getBearerToken(): string
    {
        $encrypted = (string) $this->scopeConfig->getValue(self::XML_PATH_BEARER_TOKEN);
        return $this->encryptor->decrypt($encrypted);
    }

    public function getProjectId(): string
    {
        return (string) $this->scopeConfig->getValue(self::XML_PATH_PROJECT_ID);
    }

    public function getConnectTimeout(): int
    {
        return max(1, (int) $this->scopeConfig->getValue(self::XML_PATH_CONNECT_TIMEOUT));
    }

    public function getTransferTimeout(): int
    {
        return max(1, (int) $this->scopeConfig->getValue(self::XML_PATH_TRANSFER_TIMEOUT));
    }
}
