<?php

declare(strict_types=1);

namespace Upcron\Monitor\Service;

use Psr\Log\LoggerInterface;
use Upcron\Monitor\Api\Data\HeartbeatInterface;
use Upcron\Monitor\Model\HeartbeatRepository;

class PingService
{
    private const SIGNAL_START = 'start';
    private const SIGNAL_PING  = 'ping';
    private const SIGNAL_FAIL  = 'fail';

    public function __construct(
        private readonly HeartbeatRepository $heartbeatRepository,
        private readonly UpcronApiClient $apiClient,
        private readonly LoggerInterface $logger,
    ) {
    }

    /**
     * Send a start/ping/fail signal to Upcron for the given job.
     *
     * Silently skips if the job is not in the DB, not synced (no uuid), or is excluded.
     * Never throws — all errors are logged and discarded.
     */
    public function signal(string $jobCode, string $signal): void
    {
        try {
            $heartbeat = $this->heartbeatRepository->getByJobCode($jobCode);

            if ($heartbeat === null || !$heartbeat->getHeartbeatUuid()) {
                return;
            }

            if ($heartbeat->getIsExcluded()) {
                return;
            }

            // 'start' signal is only sent in start_stop mode; default mode sends only the final ping
            if ($signal === self::SIGNAL_START
                && $heartbeat->getPingMode() !== HeartbeatInterface::PING_MODE_START_STOP
            ) {
                return;
            }

            $url = match ($signal) {
                self::SIGNAL_START => $heartbeat->getStartUrl(),
                self::SIGNAL_PING  => $heartbeat->getPingUrl(),
                self::SIGNAL_FAIL  => $heartbeat->getFailUrl(),
                default            => null,
            };

            if (!$url) {
                return;
            }

            $this->apiClient->ping($url);
        } catch (\Throwable $e) {
            $this->logger->error(
                '[upcron_monitor] PingService: unexpected error | job_code=' . $jobCode .
                ' | signal=' . $signal . ' | error=' . $e->getMessage()
            );
        }
    }
}
