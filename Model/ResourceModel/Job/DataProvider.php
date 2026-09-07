<?php

declare(strict_types=1);

namespace Upcron\Monitor\Model\ResourceModel\Job;

use Magento\Ui\DataProvider\AbstractDataProvider;
use Upcron\Monitor\Api\Data\HeartbeatInterface;
use Upcron\Monitor\Model\HeartbeatRepository;
use Upcron\Monitor\Service\JobDiscoveryService;

class DataProvider extends AbstractDataProvider
{
    public function __construct(
        string $name,
        string $primaryFieldName,
        string $requestFieldName,
        private readonly JobDiscoveryService $jobDiscoveryService,
        private readonly HeartbeatRepository $heartbeatRepository,
        array $meta = [],
        array $data = [],
    ) {
        parent::__construct($name, $primaryFieldName, $requestFieldName, $meta, $data);
    }

    public function addFilter(\Magento\Framework\Api\Filter $filter): void
    {
        // in-memory provider — filtering not implemented
    }

    public function addOrder($field, $direction): void
    {
        // in-memory provider — sorting not implemented
    }

    public function setLimit($offset, $size): void
    {
        // in-memory provider — pagination not implemented
    }

    public function getSearchResult(): \Magento\Framework\Api\Search\SearchResultInterface
    {
        throw new \RuntimeException('SearchResult not supported for in-memory DataProvider');
    }

    /**
     * @return array{totalRecords: int, items: array<int, array<string, mixed>>}
     */
    public function getData(): array
    {
        $discovered = $this->jobDiscoveryService->discover();

        $dbRecords = [];
        foreach ($this->heartbeatRepository->getAll() as $heartbeat) {
            $dbRecords[$heartbeat->getJobCode()] = $heartbeat;
        }

        $items = [];
        foreach ($discovered as $jobCode => $jobData) {
            $heartbeat = $dbRecords[$jobCode] ?? null;

            $items[] = [
                'job_code'             => $jobCode,
                'cron_schedule'        => $jobData['cron_schedule'],
                'grace_period_seconds' => $heartbeat ? $heartbeat->getGracePeriodSeconds() : $jobData['grace_period_seconds'],
                'group'                => $jobData['group'],
                'heartbeat_uuid'       => $heartbeat ? ($heartbeat->getHeartbeatUuid() ?? '') : '',
                'synced_at'            => $heartbeat ? ($heartbeat->getSyncedAt() ?? '') : '',
                'is_excluded'          => $heartbeat ? (int) $heartbeat->getIsExcluded() : 0,
                'ping_mode'            => $heartbeat ? $heartbeat->getPingMode() : HeartbeatInterface::PING_MODE_PING,
                'sync_status'          => $heartbeat && $heartbeat->getHeartbeatUuid()
                    ? __('Synced')->render()
                    : (!($jobData['is_schedule_resolved'] ?? true)
                        ? __('Schedule not configured')->render()
                        : __('Not synced')->render()),
            ];
        }

        return [
            'totalRecords' => count($items),
            'items'        => $items,
        ];
    }
}
