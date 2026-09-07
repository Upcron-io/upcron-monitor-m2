<?php

declare(strict_types=1);

namespace Upcron\Monitor\Model;

use Magento\Framework\Exception\CouldNotSaveException;
use Upcron\Monitor\Api\Data\HeartbeatInterface;
use Upcron\Monitor\Model\ResourceModel\Heartbeat as HeartbeatResource;
use Upcron\Monitor\Model\ResourceModel\Heartbeat\Collection;
use Upcron\Monitor\Model\ResourceModel\Heartbeat\CollectionFactory;

class HeartbeatRepository
{
    public function __construct(
        private readonly HeartbeatFactory $heartbeatFactory,
        private readonly HeartbeatResource $heartbeatResource,
        private readonly CollectionFactory $collectionFactory,
    ) {
    }

    public function getByJobCode(string $jobCode): ?HeartbeatInterface
    {
        /** @var Collection $collection */
        $collection = $this->collectionFactory->create();
        $collection->addFieldToFilter(HeartbeatInterface::FIELD_JOB_CODE, ['eq' => $jobCode]);
        $collection->setPageSize(1);

        /** @var HeartbeatInterface|null $item */
        $item = $collection->getFirstItem();

        if (!$item || !$item->getId()) {
            return null;
        }

        return $item;
    }

    /**
     * @throws CouldNotSaveException
     */
    public function save(HeartbeatInterface $heartbeat): void
    {
        try {
            $this->heartbeatResource->save($heartbeat);
        } catch (\Exception $e) {
            throw new CouldNotSaveException(__('Could not save heartbeat: %1', $e->getMessage()), $e);
        }
    }

    public function deleteByJobCode(string $jobCode): void
    {
        $heartbeat = $this->getByJobCode($jobCode);
        if ($heartbeat !== null) {
            $this->heartbeatResource->delete($heartbeat);
        }
    }

    /**
     * @return HeartbeatInterface[]
     */
    public function getAll(): array
    {
        /** @var Collection $collection */
        $collection = $this->collectionFactory->create();
        return $collection->getItems();
    }

    /**
     * Returns existing record by job_code, or a new unsaved model pre-populated with the job_code.
     */
    public function getOrCreate(string $jobCode): HeartbeatInterface
    {
        $existing = $this->getByJobCode($jobCode);
        if ($existing !== null) {
            return $existing;
        }

        /** @var HeartbeatInterface $heartbeat */
        $heartbeat = $this->heartbeatFactory->create();
        $heartbeat->setJobCode($jobCode);
        return $heartbeat;
    }
}
