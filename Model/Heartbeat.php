<?php

declare(strict_types=1);

namespace Upcron\Monitor\Model;

use Magento\Framework\Model\AbstractModel;
use Upcron\Monitor\Api\Data\HeartbeatInterface;
use Upcron\Monitor\Model\ResourceModel\Heartbeat as HeartbeatResource;

class Heartbeat extends AbstractModel implements HeartbeatInterface
{
    protected function _construct(): void
    {
        $this->_init(HeartbeatResource::class);
    }

    public function getId(): ?int
    {
        $id = $this->getData(self::FIELD_ID);
        return $id !== null ? (int) $id : null;
    }

    public function getJobCode(): string
    {
        return (string) $this->getData(self::FIELD_JOB_CODE);
    }

    public function setJobCode(string $jobCode): self
    {
        return $this->setData(self::FIELD_JOB_CODE, $jobCode);
    }

    public function getHeartbeatUuid(): ?string
    {
        return $this->getData(self::FIELD_HEARTBEAT_UUID);
    }

    public function setHeartbeatUuid(?string $uuid): self
    {
        return $this->setData(self::FIELD_HEARTBEAT_UUID, $uuid);
    }

    public function getCronSchedule(): string
    {
        return (string) $this->getData(self::FIELD_CRON_SCHEDULE);
    }

    public function setCronSchedule(string $schedule): self
    {
        return $this->setData(self::FIELD_CRON_SCHEDULE, $schedule);
    }

    public function getGracePeriodSeconds(): int
    {
        return (int) $this->getData(self::FIELD_GRACE_PERIOD_SECONDS);
    }

    public function setGracePeriodSeconds(int $seconds): self
    {
        return $this->setData(self::FIELD_GRACE_PERIOD_SECONDS, $seconds);
    }

    public function getPingUrl(): ?string
    {
        return $this->getData(self::FIELD_PING_URL);
    }

    public function setPingUrl(?string $url): self
    {
        return $this->setData(self::FIELD_PING_URL, $url);
    }

    public function getStartUrl(): ?string
    {
        return $this->getData(self::FIELD_START_URL);
    }

    public function setStartUrl(?string $url): self
    {
        return $this->setData(self::FIELD_START_URL, $url);
    }

    public function getFailUrl(): ?string
    {
        return $this->getData(self::FIELD_FAIL_URL);
    }

    public function setFailUrl(?string $url): self
    {
        return $this->setData(self::FIELD_FAIL_URL, $url);
    }

    public function getIsExcluded(): bool
    {
        return (bool) $this->getData(self::FIELD_IS_EXCLUDED);
    }

    public function setIsExcluded(bool $excluded): self
    {
        return $this->setData(self::FIELD_IS_EXCLUDED, $excluded);
    }

    public function getPingMode(): string
    {
        return (string) ($this->getData(self::FIELD_PING_MODE) ?: self::PING_MODE_PING);
    }

    public function setPingMode(string $mode): self
    {
        return $this->setData(self::FIELD_PING_MODE, $mode);
    }

    public function getSyncedAt(): ?string
    {
        return $this->getData(self::FIELD_SYNCED_AT);
    }

    public function setSyncedAt(?string $syncedAt): self
    {
        return $this->setData(self::FIELD_SYNCED_AT, $syncedAt);
    }
}
