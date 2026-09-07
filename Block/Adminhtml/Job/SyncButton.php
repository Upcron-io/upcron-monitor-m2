<?php

declare(strict_types=1);

namespace Upcron\Monitor\Block\Adminhtml\Job;

use Magento\Backend\Block\Template;
use Magento\Backend\Block\Template\Context;

class SyncButton extends Template
{
    public function __construct(Context $context, array $data = [])
    {
        parent::__construct($context, $data);
    }

    public function getSyncUrl(): string
    {
        return $this->getUrl('upcron_monitor/sync/execute');
    }
}
