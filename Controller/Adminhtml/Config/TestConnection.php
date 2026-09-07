<?php

declare(strict_types=1);

namespace Upcron\Monitor\Controller\Adminhtml\Config;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Exception\LocalizedException;
use Upcron\Monitor\Service\UpcronApiClient;

class TestConnection extends Action implements HttpPostActionInterface
{
    public const ADMIN_RESOURCE = 'Upcron_Monitor::config';

    public function __construct(
        Context $context,
        private readonly UpcronApiClient $apiClient,
        private readonly JsonFactory $jsonFactory,
    ) {
        parent::__construct($context);
    }

    public function execute(): Json
    {
        $result = $this->jsonFactory->create();

        try {
            $this->apiClient->testConnection();
            $result->setData(['success' => true, 'message' => (string) __('Connection successful')]);
        } catch (LocalizedException $e) {
            $result->setData(['success' => false, 'message' => $e->getMessage()]);
        } catch (\Throwable $e) {
            $result->setData([
                'success' => false,
                'message' => (string) __('Unexpected error: %1', $e->getMessage()),
            ]);
        }

        return $result;
    }
}
