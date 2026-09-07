<?php

declare(strict_types=1);

namespace Upcron\Monitor\Model\Config\Source;

use Magento\Framework\Data\OptionSourceInterface;
use Upcron\Monitor\Api\Data\HeartbeatInterface;

class PingMode implements OptionSourceInterface
{
    public function toOptionArray(): array
    {
        return [
            ['value' => HeartbeatInterface::PING_MODE_PING,       'label' => __('Ping')],
            ['value' => HeartbeatInterface::PING_MODE_START_STOP, 'label' => __('Start / Stop')],
        ];
    }
}
