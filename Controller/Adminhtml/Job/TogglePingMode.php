<?php

declare(strict_types=1);

namespace Upcron\Monitor\Controller\Adminhtml\Job;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Controller\Result\Redirect;
use Magento\Framework\Exception\CouldNotSaveException;
use Upcron\Monitor\Api\Data\HeartbeatInterface;
use Upcron\Monitor\Model\HeartbeatRepository;
use Upcron\Monitor\Service\JobDiscoveryService;

class TogglePingMode extends Action implements HttpGetActionInterface, HttpPostActionInterface
{
    public const ADMIN_RESOURCE = 'Upcron_Monitor::jobs';

    private const ALLOWED_MODES = [
        HeartbeatInterface::PING_MODE_PING,
        HeartbeatInterface::PING_MODE_START_STOP,
    ];

    public function __construct(
        Context $context,
        private readonly HeartbeatRepository $heartbeatRepository,
        private readonly JobDiscoveryService $jobDiscoveryService,
        private readonly JsonFactory $jsonFactory,
    ) {
        parent::__construct($context);
    }

    public function execute(): Json|Redirect
    {
        $jobCode = (string) $this->getRequest()->getParam('job_code', '');
        $pingMode = (string) $this->getRequest()->getParam('ping_mode', HeartbeatInterface::PING_MODE_PING);
        $isAjax = (bool) $this->getRequest()->getParam('isAjax', false)
            || $this->getRequest()->isXmlHttpRequest();

        if ($jobCode === '') {
            if ($isAjax) {
                return $this->jsonFactory->create()
                    ->setData(['success' => false, 'message' => 'Missing job_code parameter']);
            }
            $this->messageManager->addErrorMessage(__('Missing job_code parameter'));
            return $this->resultRedirectFactory->create()->setPath('upcron_monitor/job/index');
        }

        if (!in_array($pingMode, self::ALLOWED_MODES, true)) {
            $pingMode = HeartbeatInterface::PING_MODE_PING;
        }

        try {
            $heartbeat = $this->heartbeatRepository->getOrCreate($jobCode);

            if (!$heartbeat->getId()) {
                $discovered = $this->jobDiscoveryService->discover();
                if (isset($discovered[$jobCode])) {
                    $heartbeat->setCronSchedule($discovered[$jobCode]['cron_schedule']);
                    $heartbeat->setGracePeriodSeconds($discovered[$jobCode]['grace_period_seconds']);
                } else {
                    $heartbeat->setCronSchedule('');
                    $heartbeat->setGracePeriodSeconds(3600);
                }
            }

            $heartbeat->setPingMode($pingMode);
            $this->heartbeatRepository->save($heartbeat);

            if ($isAjax) {
                return $this->jsonFactory->create()
                    ->setData(['success' => true, 'ping_mode' => $pingMode]);
            }

            $label = $pingMode === HeartbeatInterface::PING_MODE_START_STOP ? __('Start/Stop') : __('Ping');
            $this->messageManager->addSuccessMessage(__('Job "%1" ping mode set to %2.', $jobCode, $label));
        } catch (CouldNotSaveException $e) {
            if ($isAjax) {
                return $this->jsonFactory->create()
                    ->setData(['success' => false, 'message' => $e->getMessage()]);
            }
            $this->messageManager->addErrorMessage($e->getMessage());
        } catch (\Throwable $e) {
            if ($isAjax) {
                return $this->jsonFactory->create()
                    ->setData(['success' => false, 'message' => (string) __('Unexpected error: %1', $e->getMessage())]);
            }
            $this->messageManager->addErrorMessage(__('Unexpected error: %1', $e->getMessage()));
        }

        return $this->resultRedirectFactory->create()->setPath('upcron_monitor/job/index');
    }
}
