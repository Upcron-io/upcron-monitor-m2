<?php

declare(strict_types=1);

namespace Upcron\Monitor\Ui\Component\Listing\Column;

use Magento\Framework\UrlInterface;
use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Ui\Component\Listing\Columns\Column;
use Upcron\Monitor\Api\Data\HeartbeatInterface;

class ExclusionActions extends Column
{
    public function __construct(
        ContextInterface $context,
        UiComponentFactory $uiComponentFactory,
        private readonly UrlInterface $urlBuilder,
        array $components = [],
        array $data = [],
    ) {
        parent::__construct($context, $uiComponentFactory, $components, $data);
    }

    /**
     * @param array<string, mixed> $dataSource
     * @return array<string, mixed>
     */
    public function prepareDataSource(array $dataSource): array
    {
        if (!isset($dataSource['data']['items'])) {
            return $dataSource;
        }

        foreach ($dataSource['data']['items'] as &$item) {
            $jobCode = $item['job_code'] ?? '';
            $isExcluded = (bool) ($item['is_excluded'] ?? false);
            $pingMode = (string) ($item['ping_mode'] ?? HeartbeatInterface::PING_MODE_PING);

            $actions = [];

            $actions['edit'] = [
                'href'  => $this->urlBuilder->getUrl(
                    'upcron_monitor/job/edit',
                    ['job_code' => $jobCode]
                ),
                'label' => __('Edit'),
            ];

            if ($isExcluded) {
                $actions['include'] = [
                    'href'  => $this->urlBuilder->getUrl(
                        'upcron_monitor/job/toggleExclusion',
                        ['job_code' => $jobCode, 'excluded' => 0]
                    ),
                    'label' => __('Include'),
                ];
            } else {
                $actions['exclude'] = [
                    'href'  => $this->urlBuilder->getUrl(
                        'upcron_monitor/job/toggleExclusion',
                        ['job_code' => $jobCode, 'excluded' => 1]
                    ),
                    'label' => __('Exclude'),
                ];

                if ($pingMode === HeartbeatInterface::PING_MODE_START_STOP) {
                    $actions['ping_mode'] = [
                        'href'  => $this->urlBuilder->getUrl(
                            'upcron_monitor/job/togglePingMode',
                            ['job_code' => $jobCode, 'ping_mode' => HeartbeatInterface::PING_MODE_PING]
                        ),
                        'label' => __('Switch to Ping'),
                    ];
                } else {
                    $actions['ping_mode'] = [
                        'href'  => $this->urlBuilder->getUrl(
                            'upcron_monitor/job/togglePingMode',
                            ['job_code' => $jobCode, 'ping_mode' => HeartbeatInterface::PING_MODE_START_STOP]
                        ),
                        'label' => __('Switch to Start/Stop'),
                    ];
                }
            }

            $item[$this->getData('name')] = $actions;
        }

        return $dataSource;
    }
}
