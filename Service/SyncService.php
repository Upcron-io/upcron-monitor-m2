<?php

declare(strict_types=1);

namespace Upcron\Monitor\Service;

use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Stdlib\DateTime\DateTime;
use Psr\Log\LoggerInterface;
use Upcron\Monitor\Api\Data\HeartbeatInterface;
use Upcron\Monitor\Model\Config\ConfigProvider;
use Upcron\Monitor\Model\HeartbeatRepository;

class SyncService
{
    public function __construct(
        private readonly JobDiscoveryService $jobDiscoveryService,
        private readonly HeartbeatRepository $heartbeatRepository,
        private readonly UpcronApiClient $apiClient,
        private readonly ConfigProvider $configProvider,
        private readonly DateTime $dateTime,
        private readonly LoggerInterface $logger,
    ) {
    }

    /**
     * Sync all non-excluded discovered jobs to Upcron.
     *
     * Persists each successful API response before processing the next job.
     * If a later request fails, previously confirmed heartbeats remain reflected
     * in Magento and the sync stops immediately.
     *
     * @return array{synced: int, errors: string[]}
     */
    public function sync(bool $force = false): array
    {
        $projectId = $this->configProvider->getProjectId();
        $discovered = $this->jobDiscoveryService->discover();

        // Load existing DB records keyed by job_code
        $existing = [];
        foreach ($this->heartbeatRepository->getAll() as $heartbeat) {
            $existing[$heartbeat->getJobCode()] = $heartbeat;
        }

        // Build list of jobs to sync (non-excluded, schedule resolved)
        $toSync = [];
        foreach ($discovered as $jobCode => $jobData) {
            $heartbeat = $existing[$jobCode] ?? null;
            if ($heartbeat && $heartbeat->getIsExcluded()) {
                continue;
            }
            if (!($jobData['is_schedule_resolved'] ?? true)) {
                continue; // Schedule not resolvable — skip API sync
            }
            $toSync[$jobCode] = [
                'job_data'  => $jobData,
                'heartbeat' => $heartbeat,
            ];
        }

        if (empty($toSync)) {
            $this->logger->info('[upcron_monitor] SyncService: no jobs to sync');
            return ['synced' => 0, 'errors' => []];
        }

        $synced = 0;
        $now = $this->dateTime->gmtDate();
        foreach ($toSync as $jobCode => $entry) {
            $jobData  = $entry['job_data'];
            $heartbeat = $entry['heartbeat'];
            $existingUuid = $heartbeat ? $heartbeat->getHeartbeatUuid() : null;

            $payload = [
                'name'          => $jobCode,
                'project_id'    => $projectId,
                'grace_period'  => $heartbeat ? $heartbeat->getGracePeriodSeconds() : $jobData['grace_period_seconds'],
                'cron_schedule' => $jobData['cron_schedule'],
            ];

            try {
                if ($existingUuid && !$force) {
                    try {
                        $response = $this->apiClient->updateHeartbeat($existingUuid, $payload);
                    } catch (LocalizedException $e) {
                        if (!str_contains($e->getMessage(), '404')) {
                            throw $e;
                        }
                        // Heartbeat no longer exists on Upcron — recreate it
                        $this->logger->warning(
                            '[upcron_monitor] SyncService: heartbeat not found on Upcron, recreating | job_code=' . $jobCode
                        );
                        $response = $this->apiClient->createHeartbeat($payload);
                    }
                } else {
                    $response = $this->apiClient->createHeartbeat($payload);
                }

                $data = $response['data'] ?? $response;
                $result = [
                    'uuid'      => $data['id'] ?? ($existingUuid ?? ''),
                    'ping_url'  => $data['ping_url'] ?? '',
                    'start_url' => $data['start_url'] ?? '',
                    'fail_url'  => $data['fail_url'] ?? '',
                    'job_data'  => $jobData,
                ];

                $this->persistResult($jobCode, $result, $existing[$jobCode] ?? null, $now);
                $synced++;

                $this->logger->info(
                    '[upcron_monitor] SyncService: synced | job_code=' . $jobCode .
                    ' | uuid=' . $result['uuid']
                );
            } catch (LocalizedException $e) {
                $this->logger->error(
                    '[upcron_monitor] SyncService: API error | job_code=' . $jobCode .
                    ' | error=' . $e->getMessage()
                );
                $this->logger->warning(
                    '[upcron_monitor] SyncService: partial sync interrupted | synced=' . $synced
                );

                return [
                    'synced' => $synced,
                    'errors' => [$e->getMessage()],
                ];
            }
        }

        $this->logger->info('[upcron_monitor] SyncService: sync completed | synced=' . $synced . ' | errors=0');

        return ['synced' => $synced, 'errors' => []];
    }

    /**
     * @param array{uuid: string, ping_url: string, start_url: string, fail_url: string, job_data: array<string, mixed>} $result
     */
    private function persistResult(string $jobCode, array $result, ?HeartbeatInterface $heartbeat, string $now): void
    {
        if ($heartbeat === null) {
            $heartbeat = $this->heartbeatRepository->getOrCreate($jobCode);
        }

        $heartbeat->setJobCode($jobCode);
        $heartbeat->setCronSchedule($result['job_data']['cron_schedule']);
        $heartbeat->setGracePeriodSeconds($result['job_data']['grace_period_seconds']);
        $heartbeat->setHeartbeatUuid($result['uuid']);
        $heartbeat->setPingUrl($result['ping_url'] ?: null);
        $heartbeat->setStartUrl($result['start_url'] ?: null);
        $heartbeat->setFailUrl($result['fail_url'] ?: null);
        $heartbeat->setSyncedAt($now);

        $this->heartbeatRepository->save($heartbeat);
    }
}
