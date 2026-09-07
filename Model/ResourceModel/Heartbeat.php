<?php

declare(strict_types=1);

namespace Upcron\Monitor\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;
use Upcron\Monitor\Api\Data\HeartbeatInterface;

class Heartbeat extends AbstractDb
{
    protected function _construct(): void
    {
        $this->_init(HeartbeatInterface::TABLE_NAME, HeartbeatInterface::FIELD_ID);
    }
}
