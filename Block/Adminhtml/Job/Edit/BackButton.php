<?php

declare(strict_types=1);

namespace Upcron\Monitor\Block\Adminhtml\Job\Edit;

use Magento\Framework\View\Element\UiComponent\Control\ButtonProviderInterface;

class BackButton implements ButtonProviderInterface
{
    public function getButtonData(): array
    {
        return [
            'label'    => __('Back'),
            'on_click' => 'history.back()',
            'class'    => 'back',
            'sort_order' => 10,
        ];
    }
}
