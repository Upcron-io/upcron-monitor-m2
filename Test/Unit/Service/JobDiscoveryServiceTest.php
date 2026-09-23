<?php

declare(strict_types=1);

namespace Upcron\Monitor\Test\Unit\Service;

use Magento\Cron\Model\Config\Data as CronConfig;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Upcron\Monitor\Service\CronExpressionParser;
use Upcron\Monitor\Service\JobDiscoveryService;

class JobDiscoveryServiceTest extends TestCase
{
    private CronConfig|MockObject $cronConfig;
    private ScopeConfigInterface|MockObject $scopeConfig;

    #[\Override]
    protected function setUp(): void
    {
        $this->cronConfig = $this->createMock(CronConfig::class);
        $this->scopeConfig = $this->createMock(ScopeConfigInterface::class);
    }

    public function testResolvesConfigPathScheduleUsingStoreScope(): void
    {
        $path = 'crontab/default/jobs/currency_rates_update/schedule/cron_expr';
        $this->cronConfig->method('getJobs')->willReturn([
            'default' => [
                'currency_rates_update' => ['config_path' => $path],
            ],
        ]);
        $this->scopeConfig->expects(self::once())
            ->method('getValue')
            ->with($path, ScopeInterface::SCOPE_STORE)
            ->willReturn('0 3 * * *');

        $jobs = $this->createService()->discover();

        self::assertSame('0 3 * * *', $jobs['currency_rates_update']['cron_schedule']);
        self::assertTrue($jobs['currency_rates_update']['is_schedule_resolved']);
        self::assertSame(32767, $jobs['currency_rates_update']['grace_period_seconds']);
    }

    public function testKeepsUnconfiguredConfigPathJobVisibleWithEmptySchedule(): void
    {
        $path = 'crontab/default/jobs/currency_rates_update/schedule/cron_expr';
        $this->cronConfig->method('getJobs')->willReturn([
            'default' => [
                'currency_rates_update' => ['config_path' => $path],
            ],
        ]);
        $this->scopeConfig->expects(self::once())
            ->method('getValue')
            ->with($path, ScopeInterface::SCOPE_STORE)
            ->willReturn(null);

        $jobs = $this->createService()->discover();

        self::assertSame('', $jobs['currency_rates_update']['cron_schedule']);
        self::assertFalse($jobs['currency_rates_update']['is_schedule_resolved']);
    }

    private function createService(): JobDiscoveryService
    {
        return new JobDiscoveryService(
            $this->cronConfig,
            $this->scopeConfig,
            new CronExpressionParser(),
        );
    }
}
