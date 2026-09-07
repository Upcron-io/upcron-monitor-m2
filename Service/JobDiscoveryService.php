<?php

declare(strict_types=1);

namespace Upcron\Monitor\Service;

use Magento\Cron\Model\Config\Data as CronConfig;
use Magento\Framework\App\Config\ScopeConfigInterface;

class JobDiscoveryService
{
    private const DEFAULT_GRACE_PERIOD = 3600;
    private const MIN_GRACE_PERIOD = 60;
    private const MAX_GRACE_PERIOD = 32767;

    public function __construct(
        private readonly CronConfig $cronConfig,
        private readonly ScopeConfigInterface $scopeConfig,
        private readonly CronExpressionParser $expressionParser,
    ) {
    }

    /**
     * Discover all Magento cron jobs from all installed modules.
     *
     * @return array<string, array{job_code: string, cron_schedule: string, grace_period_seconds: int, group: string, is_schedule_resolved: bool}>
     */
    public function discover(): array
    {
        $jobs = [];
        $allGroups = $this->cronConfig->getJobs();

        foreach ($allGroups as $groupId => $groupJobs) {
            if (!is_array($groupJobs)) {
                continue;
            }
            foreach ($groupJobs as $jobName => $jobConfig) {
                if (!is_array($jobConfig)) {
                    continue;
                }

                [$schedule, $isResolved] = $this->resolveSchedule($jobConfig);

                // Skip jobs with no schedule — they cannot be heartbeat-monitored
                if ($schedule === '') {
                    continue;
                }

                $gracePeriod = $isResolved
                    ? $this->computeGracePeriod($schedule)
                    : self::DEFAULT_GRACE_PERIOD;

                $jobs[(string) $jobName] = [
                    'job_code'              => (string) $jobName,
                    'cron_schedule'         => $schedule,
                    'grace_period_seconds'  => $gracePeriod,
                    'group'                 => (string) $groupId,
                    'is_schedule_resolved'  => $isResolved,
                ];
            }
        }

        ksort($jobs);
        return $jobs;
    }

    /**
     * @param array<string, mixed> $jobConfig
     * @return array{0: string, 1: bool} [schedule, wasResolved]
     */
    private function resolveSchedule(array $jobConfig): array
    {
        // Direct schedule expression
        if (!empty($jobConfig['schedule'])) {
            return [(string) $jobConfig['schedule'], true];
        }

        // Config path — resolve at sync time
        if (!empty($jobConfig['config_path'])) {
            $configPath = (string) $jobConfig['config_path'];
            $resolved = $this->scopeConfig->getValue($configPath, ScopeConfigInterface::SCOPE_TYPE_DEFAULT);

            if ($resolved !== null && $resolved !== '') {
                return [(string) $resolved, true];
            }

            // Unresolvable — return raw config path, mark as not resolved
            return [$configPath, false];
        }

        return ['', false];
    }

    private function computeGracePeriod(string $schedule): int
    {
        $intervalSeconds = $this->expressionParser->toIntervalSeconds($schedule);

        if ($intervalSeconds === null || $intervalSeconds <= 0) {
            return self::DEFAULT_GRACE_PERIOD;
        }

        $grace = (int) floor($intervalSeconds * 2);
        return max(self::MIN_GRACE_PERIOD, min(self::MAX_GRACE_PERIOD, $grace));
    }
}
