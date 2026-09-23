<?php

declare(strict_types=1);

namespace Upcron\Monitor\Test\Unit\Block\Adminhtml\Job;

use Magento\Backend\Block\Template\Context;
use Magento\Framework\Exception\LocalizedException;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Upcron\Monitor\Block\Adminhtml\Job\SubscriptionUsage;
use Upcron\Monitor\Model\Config\ConfigProvider;
use Upcron\Monitor\Service\UpcronApiClient;

class SubscriptionUsageTest extends TestCase
{
    private ConfigProvider|MockObject $configProvider;
    private UpcronApiClient|MockObject $apiClient;

    #[\Override]
    protected function setUp(): void
    {
        $this->configProvider = $this->createMock(ConfigProvider::class);
        $this->apiClient = $this->createMock(UpcronApiClient::class);
    }

    public function testReturnsHeartbeatUsageForSuccessfulResponse(): void
    {
        $this->configProvider->method('getBearerToken')->willReturn('token');
        $this->apiClient->method('getSubscriptionUsage')->willReturn([
            'data' => [
                'usage' => [
                    'heartbeats' => [
                        'current' => 20,
                        'remaining' => 5,
                        'limit' => 25,
                    ],
                ],
            ],
        ]);

        self::assertSame([
            'status' => 'available',
            'used' => 20,
            'available' => 5,
            'limit' => 25,
        ], $this->createBlock()->getDisplayData());
    }

    public function testReturnsConfigurationMessageWithoutBearerToken(): void
    {
        $this->configProvider->method('getBearerToken')->willReturn('');
        $this->apiClient->expects(self::never())->method('getSubscriptionUsage');

        self::assertSame([
            'status' => 'unavailable',
            'message' => 'Bearer token is not configured. Check the Upcron Monitor configuration.',
        ], $this->createBlock()->getDisplayData());
    }

    public function testReturnsUnavailableMessageWhenUsageRequestFails(): void
    {
        $this->configProvider->method('getBearerToken')->willReturn('token');
        $this->apiClient->method('getSubscriptionUsage')
            ->willThrowException(new LocalizedException(__('Unauthorized')));

        self::assertSame([
            'status' => 'unavailable',
            'message' => 'Subscription usage is unavailable. Check the Upcron Monitor configuration.',
        ], $this->createBlock()->getDisplayData());
    }

    private function createBlock(): SubscriptionUsage
    {
        return new SubscriptionUsage(
            $this->createMock(Context::class),
            $this->configProvider,
            $this->apiClient,
        );
    }
}
