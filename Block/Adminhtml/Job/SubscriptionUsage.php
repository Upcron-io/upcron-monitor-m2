<?php

declare(strict_types=1);

namespace Upcron\Monitor\Block\Adminhtml\Job;

use Magento\Backend\Block\Template;
use Magento\Backend\Block\Template\Context;
use Magento\Framework\Exception\LocalizedException;
use Upcron\Monitor\Model\Config\ConfigProvider;
use Upcron\Monitor\Service\UpcronApiClient;

class SubscriptionUsage extends Template
{
    private const TOKEN_NOT_CONFIGURED_MESSAGE =
        'Bearer token is not configured. Check the Upcron Monitor configuration.';
    private const UNAVAILABLE_MESSAGE =
        'Subscription usage is unavailable. Check the Upcron Monitor configuration.';

    public function __construct(
        Context $context,
        private readonly ConfigProvider $configProvider,
        private readonly UpcronApiClient $apiClient,
        array $data = [],
    ) {
        parent::__construct($context, $data);
    }

    /**
     * @return array{status: string, used?: int, available?: int, limit?: int, message?: string}
     */
    public function getDisplayData(): array
    {
        if (trim($this->configProvider->getBearerToken()) === '') {
            return [
                'status' => 'unavailable',
                'message' => self::TOKEN_NOT_CONFIGURED_MESSAGE,
            ];
        }

        try {
            $response = $this->apiClient->getSubscriptionUsage();
            $heartbeats = $response['data']['usage']['heartbeats'] ?? null;

            if (!is_array($heartbeats)
                || !isset($heartbeats['current'], $heartbeats['remaining'], $heartbeats['limit'])
            ) {
                return [
                    'status' => 'unavailable',
                    'message' => self::UNAVAILABLE_MESSAGE,
                ];
            }

            return [
                'status' => 'available',
                'used' => (int) $heartbeats['current'],
                'available' => (int) $heartbeats['remaining'],
                'limit' => (int) $heartbeats['limit'],
            ];
        } catch (LocalizedException) {
            return [
                'status' => 'unavailable',
                'message' => self::UNAVAILABLE_MESSAGE,
            ];
        }
    }
}
