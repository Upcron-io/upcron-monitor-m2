<?php

declare(strict_types=1);

namespace Upcron\Monitor\Api\Data;

/**
 * Heartbeat data interface
 *
 * @api
 */
interface HeartbeatInterface
{
    public const TABLE_NAME = 'upcron_monitor_heartbeat';

    public const FIELD_ID = 'id';
    public const FIELD_JOB_CODE = 'job_code';
    public const FIELD_HEARTBEAT_UUID = 'heartbeat_uuid';
    public const FIELD_CRON_SCHEDULE = 'cron_schedule';
    public const FIELD_GRACE_PERIOD_SECONDS = 'grace_period_seconds';
    public const FIELD_PING_URL = 'ping_url';
    public const FIELD_START_URL = 'start_url';
    public const FIELD_FAIL_URL = 'fail_url';
    public const FIELD_IS_EXCLUDED = 'is_excluded';
    public const FIELD_PING_MODE = 'ping_mode';
    public const FIELD_SYNCED_AT = 'synced_at';

    public const PING_MODE_PING = 'ping';
    public const PING_MODE_START_STOP = 'start_stop';

    public function getId(): ?int;

    public function getJobCode(): string;

    public function setJobCode(string $jobCode): self;

    public function getHeartbeatUuid(): ?string;

    public function setHeartbeatUuid(?string $uuid): self;

    public function getCronSchedule(): string;

    public function setCronSchedule(string $schedule): self;

    public function getGracePeriodSeconds(): int;

    public function setGracePeriodSeconds(int $seconds): self;

    public function getPingUrl(): ?string;

    public function setPingUrl(?string $url): self;

    public function getStartUrl(): ?string;

    public function setStartUrl(?string $url): self;

    public function getFailUrl(): ?string;

    public function setFailUrl(?string $url): self;

    public function getIsExcluded(): bool;

    public function setIsExcluded(bool $excluded): self;

    public function getPingMode(): string;

    public function setPingMode(string $mode): self;

    public function getSyncedAt(): ?string;

    public function setSyncedAt(?string $syncedAt): self;
}
