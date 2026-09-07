<?php

declare(strict_types=1);

namespace Upcron\Monitor\Model\ResourceModel\Job;

use Magento\Framework\App\RequestInterface;
use Magento\Ui\DataProvider\AbstractDataProvider;
use Upcron\Monitor\Api\Data\HeartbeatInterface;
use Upcron\Monitor\Model\HeartbeatRepository;
use Upcron\Monitor\Service\JobDiscoveryService;

class EditDataProvider extends AbstractDataProvider
{
    public function __construct(
        string $name,
        string $primaryFieldName,
        string $requestFieldName,
        private readonly RequestInterface $request,
        private readonly JobDiscoveryService $jobDiscoveryService,
        private readonly HeartbeatRepository $heartbeatRepository,
        array $meta = [],
        array $data = [],
    ) {
        parent::__construct($name, $primaryFieldName, $requestFieldName, $meta, $data);
    }

    public function addFilter(\Magento\Framework\Api\Filter $filter): void
    {
        // in-memory provider — filtering not supported
    }

    public function count(): int
    {
        return 0;
    }

    public function getData(): array
    {
        $jobCode  = (string) $this->request->getParam('job_code', '');
        $jobData  = $this->jobDiscoveryService->discover()[$jobCode] ?? [];
        $heartbeat = $this->heartbeatRepository->getByJobCode($jobCode);

        return [
            $jobCode => [
                'job_code'             => $jobCode,
                'grace_period_seconds' => $heartbeat
                    ? $heartbeat->getGracePeriodSeconds()
                    : ($jobData['grace_period_seconds'] ?? 3600),
                'is_excluded'          => $heartbeat ? (string)(int) $heartbeat->getIsExcluded() : '0',
                'ping_mode'            => $heartbeat
                    ? $heartbeat->getPingMode()
                    : HeartbeatInterface::PING_MODE_PING,
            ],
        ];
    }
}
