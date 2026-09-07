<?php

declare(strict_types=1);

namespace Upcron\Monitor\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Upcron\Monitor\Service\PingService;

class CronJobRunObserver implements ObserverInterface
{
    public function __construct(
        private readonly PingService $pingService,
    ) {
    }

    /**
     * Fired by Magento immediately before a cron job callback is invoked.
     * Event data: job_name = "cron/{groupId}/{jobCode}"
     */
    public function execute(Observer $observer): void
    {
        try {
            $jobName = (string) $observer->getData('job_name');
            // Format: cron/{group}/{job_code}
            $parts = explode('/', $jobName);
            $jobCode = count($parts) >= 3 ? end($parts) : '';

            if ($jobCode === '') {
                return;
            }

            $this->pingService->signal($jobCode, 'start');
        } catch (\Throwable $e) {
            // Never allow exceptions to affect cron job execution
        }
    }
}
