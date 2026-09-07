<?php

declare(strict_types=1);

namespace Upcron\Monitor\Plugin;

use Magento\Cron\Model\Schedule;
use Upcron\Monitor\Model\Cron\Schedule as UpcronSchedule;
use Upcron\Monitor\Service\PingService;

class CronScheduleSavePlugin
{
    public function __construct(
        private readonly PingService $pingService,
    ) {
    }

    /**
     * Before UpcronSchedule::save() — send ping or fail signal based on the job's current status.
     *
     * Only reacts to status=success (→ ping) and status=error (→ fail).
     * The save with status=running (before job execution) is silently ignored.
     */
    public function beforeSave(UpcronSchedule $schedule): void
    {
        try {
            $status = $schedule->getStatus();

            if ($status === Schedule::STATUS_SUCCESS) {
                $this->pingService->signal($schedule->getJobCode(), 'ping');
            } elseif ($status === Schedule::STATUS_ERROR) {
                $this->pingService->signal($schedule->getJobCode(), 'fail');
            }
        } catch (\Throwable $e) {
            // Never allow exceptions to affect cron schedule persistence
        }
    }
}
