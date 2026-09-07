<?php

declare(strict_types=1);

namespace Upcron\Monitor\Controller\Adminhtml\Job;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\Controller\Result\Redirect;
use Magento\Framework\Exception\CouldNotSaveException;
use Upcron\Monitor\Api\Data\HeartbeatInterface;
use Upcron\Monitor\Model\HeartbeatRepository;
use Upcron\Monitor\Service\JobDiscoveryService;

class Save extends Action implements HttpPostActionInterface
{
    public const ADMIN_RESOURCE = 'Upcron_Monitor::jobs';

    private const ALLOWED_PING_MODES = [
        HeartbeatInterface::PING_MODE_PING,
        HeartbeatInterface::PING_MODE_START_STOP,
    ];

    public function __construct(
        Context $context,
        private readonly HeartbeatRepository $heartbeatRepository,
        private readonly JobDiscoveryService $jobDiscoveryService,
    ) {
        parent::__construct($context);
    }

    public function execute(): Redirect
    {
        $post    = $this->getRequest()->getPostValue();
        $jobCode = (string) ($post['job_code'] ?? '');

        if ($jobCode === '') {
            $this->messageManager->addErrorMessage(__('Missing job_code.'));
            return $this->resultRedirectFactory->create()->setPath('upcron_monitor/job/index');
        }

        $gracePeriod = max(60, min(86400, (int) ($post['grace_period_seconds'] ?? 3600)));
        $isExcluded  = (bool) (int) ($post['is_excluded'] ?? 0);
        $pingMode    = (string) ($post['ping_mode'] ?? HeartbeatInterface::PING_MODE_PING);

        if (!in_array($pingMode, self::ALLOWED_PING_MODES, true)) {
            $pingMode = HeartbeatInterface::PING_MODE_PING;
        }

        try {
            $heartbeat = $this->heartbeatRepository->getOrCreate($jobCode);

            if (!$heartbeat->getId()) {
                $discovered = $this->jobDiscoveryService->discover();
                $heartbeat->setCronSchedule($discovered[$jobCode]['cron_schedule'] ?? '');
            }

            $heartbeat->setGracePeriodSeconds($gracePeriod);
            $heartbeat->setIsExcluded($isExcluded);
            $heartbeat->setPingMode($pingMode);
            $this->heartbeatRepository->save($heartbeat);

            $this->messageManager->addSuccessMessage(__('Job "%1" saved.', $jobCode));
        } catch (CouldNotSaveException $e) {
            $this->messageManager->addErrorMessage($e->getMessage());
            return $this->resultRedirectFactory->create()
                ->setPath('upcron_monitor/job/edit', ['job_code' => $jobCode]);
        } catch (\Throwable $e) {
            $this->messageManager->addErrorMessage(__('Unexpected error: %1', $e->getMessage()));
            return $this->resultRedirectFactory->create()
                ->setPath('upcron_monitor/job/edit', ['job_code' => $jobCode]);
        }

        return $this->resultRedirectFactory->create()->setPath('upcron_monitor/job/index');
    }
}
