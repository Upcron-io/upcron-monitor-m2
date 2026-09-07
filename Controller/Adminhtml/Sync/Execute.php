<?php

declare(strict_types=1);

namespace Upcron\Monitor\Controller\Adminhtml\Sync;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\Result\JsonFactory;
use Upcron\Monitor\Service\SyncService;

class Execute extends Action implements HttpPostActionInterface
{
    public const ADMIN_RESOURCE = 'Upcron_Monitor::sync';

    public function __construct(
        Context $context,
        private readonly SyncService $syncService,
        private readonly JsonFactory $jsonFactory,
    ) {
        parent::__construct($context);
    }

    public function execute(): Json
    {
        $force = (bool) $this->getRequest()->getParam('force', false);

        try {
            $result = $this->syncService->sync($force);

            if (!empty($result['errors'])) {
                return $this->jsonFactory->create()->setData([
                    'success' => false,
                    'message' => 'Sync failed: ' . implode(', ', $result['errors']) . '. No changes were saved.',
                ]);
            }

            return $this->jsonFactory->create()->setData([
                'success' => true,
                'synced'  => $result['synced'],
                'errors'  => [],
            ]);
        } catch (\Throwable $e) {
            return $this->jsonFactory->create()->setData([
                'success' => false,
                'message' => (string) __('Unexpected error: %1', $e->getMessage()),
            ]);
        }
    }
}
