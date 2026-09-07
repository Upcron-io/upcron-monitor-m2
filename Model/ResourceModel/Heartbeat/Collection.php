<?php

declare(strict_types=1);

namespace Upcron\Monitor\Model\ResourceModel\Heartbeat;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;
use Upcron\Monitor\Model\Heartbeat;
use Upcron\Monitor\Model\ResourceModel\Heartbeat as HeartbeatResource;

class Collection extends AbstractCollection
{
    protected function _construct(): void
    {
        $this->_init(Heartbeat::class, HeartbeatResource::class);
    }
}
